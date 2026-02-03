<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'order_date',
        'expected_delivery',
        'status',
        'subtotal',
        'tax_amount',
        'total_amount',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2'
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->po_number = 'PO-' . date('Y') . '-' . str_pad(static::count() + 1, 4, '0', STR_PAD_LEFT);
        });
    }
}