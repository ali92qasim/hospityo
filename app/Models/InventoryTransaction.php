<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'medicine_id',
        'type',
        'quantity',
        'unit_cost',
        'total_cost',
        'reference_no',
        'supplier',
        'expiry_date',
        'batch_no',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2'
    ];

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeStockIn($query)
    {
        return $query->where('type', 'stock_in');
    }

    public function scopeStockOut($query)
    {
        return $query->where('type', 'stock_out');
    }
}