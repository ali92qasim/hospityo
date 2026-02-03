<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'generic_name',
        'brand',
        'category',
        'dosage_form',
        'strength',
        'base_unit_id',
        'purchase_unit_id',
        'dispensing_unit_id',
        'reorder_level',
        'manufacturer',
        'status'
    ];

    protected $casts = [
        // Removed unit_price and expiry_date as they're managed by inventory
    ];

    public function prescriptionItems()
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function purchaseUnit()
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }

    public function dispensingUnit()
    {
        return $this->belongsTo(Unit::class, 'dispensing_unit_id');
    }

    public function getCurrentStock()
    {
        return $this->inventoryTransactions()
            ->selectRaw('SUM(CASE WHEN type = "stock_in" THEN quantity ELSE -quantity END) as current_stock')
            ->value('current_stock') ?? 0;
    }

    public function getCurrentStockInUnit($unitId)
    {
        $baseStock = $this->getCurrentStock();
        $unit = Unit::find($unitId);
        
        if (!$unit || $unit->base_unit_id !== $this->base_unit_id) {
            return 0;
        }
        
        return $unit->convertFromBaseUnit($baseStock);
    }

    public function isLowStock()
    {
        return $this->getCurrentStock() <= $this->reorder_level;
    }
}