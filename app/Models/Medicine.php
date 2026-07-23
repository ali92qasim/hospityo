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
        'strength',
        'selling_price',
        'base_unit_id',
        'purchase_unit_id',
        'dispensing_unit_id',
        'reorder_level',
        'status',
        'manage_stock'
    ];

    protected $casts = [
        'manage_stock' => 'boolean',
    ];

    /**
     * Recommended SKU placeholder illustrating the HMIS / WHO ATC-inspired protocol.
     * Format: {ATC}-{STRENGTH}-{FORM}-{BRAND}
     */
    public const SKU_PLACEHOLDER = 'N02BE01-500MG-TAB-GSK';

    /**
     * Build a SKU base string using the HMIS clinical-attribute protocol (not enforced on manual entry).
     *
     * Segments: {THERAPEUTIC}-{STRENGTH?}-{FORM?}-{BRAND?}
     * - THERAPEUTIC: WHO ATC code (e.g. N02BE01) when supplied, else generic/name token
     * - STRENGTH:    Clinical dosage (e.g. 500MG)
     * - FORM:        Dosage form / category code (e.g. TAB, INJ)
     * - BRAND:       Optional manufacturer code (e.g. GSK)
     */
    public static function buildSkuFromAttributes(
        string $name,
        ?string $strength = null,
        ?string $categoryCode = null,
        ?string $brandName = null,
        ?string $genericName = null,
        ?string $atcCode = null,
    ): string {
        $segments = [];

        $therapeutic = self::resolveTherapeuticSegment($atcCode, $genericName, $name);
        if ($therapeutic !== '') {
            $segments[] = $therapeutic;
        }

        $strength = trim((string) $strength);
        if ($strength !== '') {
            $segments[] = strtoupper(preg_replace('/[^A-Za-z0-9.]/', '', $strength));
        }

        $categoryCode = strtoupper(trim((string) $categoryCode));
        if ($categoryCode !== '') {
            $segments[] = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $categoryCode));
        }

        $brandName = trim((string) $brandName);
        if ($brandName !== '') {
            $segments[] = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $brandName), 0, 3));
        }

        if ($segments === []) {
            $fallback = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));

            return substr($fallback, 0, min(8, strlen($fallback)));
        }

        return implode('-', $segments);
    }

    public static function skuPlaceholder(): string
    {
        return self::SKU_PLACEHOLDER;
    }

    public static function skuProtocolHint(): string
    {
        return 'Suggested HMIS format: ATC code – strength – dosage form – brand (e.g. '
            . self::SKU_PLACEHOLDER
            . '). Optional — leave blank to auto-generate, or enter any unique SKU.';
    }

    private static function resolveTherapeuticSegment(?string $atcCode, ?string $genericName, string $name): string
    {
        $atcCode = strtoupper(trim((string) $atcCode));
        if ($atcCode !== '' && preg_match('/^[A-Z]\d{2}[A-Z]{0,2}\d{0,2}$/', $atcCode)) {
            return $atcCode;
        }

        $generic = trim((string) $genericName);
        if ($generic !== '') {
            $token = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $generic));
            if ($token !== '') {
                return substr($token, 0, 7);
            }
        }

        return strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 3));
    }

    /**
     * Append a numeric suffix when the base SKU is already taken.
     */
    public static function uniqueSku(string $baseSku, ?callable $exists = null): string
    {
        $exists ??= fn (string $sku) => self::where('sku', $sku)->exists();

        $sku = $baseSku;
        $counter = 1;

        while ($exists($sku)) {
            $sku = $baseSku . '-' . str_pad((string) $counter, 3, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $sku;
    }

    /**
     * Generate SKU based on medicine attributes
     */
    public static function generateSKU($name, $strength = null, $categoryId = null, $brandId = null, $genericName = null, $atcCode = null): string
    {
        $categoryCode = $categoryId
            ? MedicineCategory::find($categoryId)?->code
            : null;

        $brandName = $brandId
            ? MedicineBrand::find($brandId)?->name
            : null;

        $baseSku = self::buildSkuFromAttributes(
            $name,
            $strength,
            $categoryCode,
            $brandName,
            $genericName,
            $atcCode,
        );

        return self::uniqueSku($baseSku);
    }

    /**
     * Check if a medicine with similar attributes already exists
     */
    public static function checkDuplicate($name, $strength = null, $categoryId = null, $brandId = null, $excludeId = null): ?self
    {
        $query = self::where('name', 'LIKE', $name);

        // Check strength match
        if ($strength) {
            $query->where('strength', $strength);
        } else {
            $query->whereNull('strength');
        }

        // Check category match
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        } else {
            $query->whereNull('category_id');
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
                    $medicine->category_id,
                    $medicine->brand_id,
                    $medicine->generic_name,
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
