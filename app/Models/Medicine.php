<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Medicine extends Model
{
    use HasFactory, Auditable, UsesTenantConnection;

    protected $fillable = [
        'name',
        'sku',
        'generic_name',
        'brand_id',
        'category_id',
        'dosage_form',
        'strength',
        'selling_price',
        'base_unit_id',
        'purchase_unit_id',
        'dispensing_unit_id',
        'reorder_level',
        'manufacturer',
        'status',
        'manage_stock'
    ];

    protected $casts = [
        'manage_stock' => 'boolean',
    ];

    /**
     * Generate SKU based on medicine attributes
     */
    public static function generateSKU($name, $strength = null, $dosageForm = null, $brandId = null): string
    {
        // Create base from medicine name (first 3 letters, uppercase)
        $nameCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 3));

        // Add strength if available
        $strengthCode = $strength ? '-' . preg_replace('/[^A-Za-z0-9]/', '', $strength) : '';

        // Add dosage form code if available
        $dosageCode = '';
        if ($dosageForm) {
            $dosageMap = [
                'tablet' => 'TAB',
                'capsule' => 'CAP',
                'syrup' => 'SYR',
                'suspension' => 'SUS',
                'injection' => 'INJ',
                'cream' => 'CRM',
                'ointment' => 'OIN',
                'gel' => 'GEL',
                'drops' => 'DRP',
                'inhaler' => 'INH',
                'powder' => 'PWD',
                'solution' => 'SOL',
                'lotion' => 'LOT',
                'spray' => 'SPR',
                'patch' => 'PAT',
            ];
            $dosageCode = '-' . ($dosageMap[$dosageForm] ?? strtoupper(substr($dosageForm, 0, 3)));
        }

        // Add brand code if available
        $brandCode = '';
        if ($brandId) {
            $brand = MedicineBrand::find($brandId);
            if ($brand) {
                $brandCode = '-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $brand->name), 0, 3));
            }
        }

        // Combine all parts
        $baseSKU = $nameCode . $strengthCode . $dosageCode . $brandCode;

        // If SKU is too short (only name code), add more from the name
        if (strlen($baseSKU) <= 3) {
            // Use more characters from name if available
            $fullNameCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
            $baseSKU = substr($fullNameCode, 0, min(8, strlen($fullNameCode)));
        }

        // Check if SKU exists, if yes, add a number suffix
        $sku = $baseSKU;
        $counter = 1;
        while (self::where('sku', $sku)->exists()) {
            $sku = $baseSKU . '-' . str_pad($counter, 3, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $sku;
    }

    /**
     * Check if a medicine with similar attributes already exists
     */
    public static function checkDuplicate($name, $strength = null, $dosageForm = null, $brandId = null, $excludeId = null): ?self
    {
        $query = self::where('name', 'LIKE', $name);

        // Check strength match
        if ($strength) {
            $query->where('strength', $strength);
        } else {
            $query->whereNull('strength');
        }

        // Check dosage form match
        if ($dosageForm) {
            $query->where('dosage_form', $dosageForm);
        } else {
            $query->whereNull('dosage_form');
        }

        // Check brand match
        if ($brandId) {
            $query->where('brand_id', $brandId);
        } else {
            $query->whereNull('brand_id');
        }

        // Exclude current medicine if updating
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    /**
     * Boot method to auto-generate SKU if not provided
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($medicine) {
            if (empty($medicine->sku)) {
                $medicine->sku = self::generateSKU(
                    $medicine->name,
                    $medicine->strength,
                    $medicine->dosage_form,
                    $medicine->brand_id
                );
            }
        });
    }

    public function prescriptionItems(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MedicineCategory::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(MedicineBrand::class, 'brand_id');
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function purchaseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }

    public function dispensingUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'dispensing_unit_id');
    }

    /**
     * Total available stock computed from remaining_quantity on stock_in transactions.
     * This is the FIFO-aware stock figure — single source of truth.
     */
    public function getTotalAvailableStock(): int
    {
        if (!$this->manage_stock) {
            return 0;
        }

        return (int) ($this->inventoryTransactions()
            ->where('type', 'stock_in')
            ->sum('remaining_quantity') ?? 0);
    }

    /**
     * Returns stock_in batches ordered by expiry_date ASC (FEFO), then created_at ASC.
     * Batches with no expiry date are consumed last.
     * Only returns batches that still have remaining stock.
     */
    public function getAvailableBatches()
    {
        return $this->inventoryTransactions()
            ->where('type', 'stock_in')
            ->where('remaining_quantity', '>', 0)
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get the effective selling price per base unit.
     * Uses the clinic-set selling_price if available, otherwise falls back
     * to the unit_cost of the most recent stock_in transaction.
     */
    public function getSellingPrice(): float
    {
        if (!is_null($this->selling_price)) {
            return (float) $this->selling_price;
        }

        $latestCost = $this->inventoryTransactions()
            ->where('type', 'stock_in')
            ->orderBy('created_at', 'desc')
            ->value('unit_cost');

        return (float) ($latestCost ?? 0);
    }

    /**
     * Legacy alias — delegates to getTotalAvailableStock() so existing
     * code calling getCurrentStock() continues to work without changes.
     */
    public function getCurrentStock(): int
    {
        return $this->getTotalAvailableStock();
    }

    public function getCurrentStockInUnit($unitId): int
    {
        if (!$this->manage_stock) {
            return 0;
        }

        $baseStock = $this->getTotalAvailableStock();
        $unit = Unit::find($unitId);

        if (!$unit || $unit->base_unit_id !== $this->base_unit_id) {
            return 0;
        }

        return $unit->convertFromBaseUnit($baseStock);
    }

    public function isLowStock(): bool
    {
        if (!$this->manage_stock) {
            return false;
        }

        return $this->getTotalAvailableStock() <= ($this->reorder_level ?? 0);
    }
}
