<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
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