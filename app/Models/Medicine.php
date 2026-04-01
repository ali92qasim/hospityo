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

    public function getCurrentStock(): int
    {
        // If manage_stock is disabled, return 0
        if (!$this->manage_stock) {
            return 0;
        }

        return $this->inventoryTransactions()
            ->selectRaw('SUM(CASE WHEN type = "stock_in" THEN quantity ELSE -quantity END) as current_stock')
            ->value('current_stock') ?? 0;
    }

    public function getCurrentStockInUnit($unitId): int
    {
        // If manage_stock is disabled, return 0
        if (!$this->manage_stock) {
            return 0;
        }

        $baseStock = $this->getCurrentStock();
        $unit = Unit::find($unitId);

        if (!$unit || $unit->base_unit_id !== $this->base_unit_id) {
            return 0;
        }

        return $unit->convertFromBaseUnit($baseStock);
    }

    public function isLowStock(): bool
    {
        // If manage_stock is disabled, never show as low stock
        if (!$this->manage_stock) {
            return false;
        }

        return $this->getCurrentStock() <= $this->reorder_level;
    }
}
