<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class InventoryTransaction extends Model
{
    use HasFactory, Auditable, UsesTenantConnection;

    protected $fillable = [
        'medicine_id',
        'type',
        'quantity',
        'remaining_quantity',
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
        'expiry_date'        => 'date',
        'unit_cost'          => 'decimal:2',
        'total_cost'         => 'decimal:2',
        'quantity'           => 'integer',
        'remaining_quantity' => 'integer',
    ];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Query stock_in batches expiring within the given number of months
     * that still have remaining stock available.
     *
     * Ordered by expiry_date ASC so the most urgent batches appear first.
     */
    public static function nearExpiry(int $months = 6)
    {
        return static::with(['medicine'])
            ->where('type', 'stock_in')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addMonths($months))
            ->where('expiry_date', '>', now())
            ->where('remaining_quantity', '>', 0)
            ->orderBy('expiry_date', 'asc');
    }
}
