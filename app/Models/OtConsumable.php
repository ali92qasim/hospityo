<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class OtConsumable extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'name', 'sku', 'category', 'unit', 'current_stock',
        'reorder_level', 'unit_cost', 'supplier_name',
        'is_reusable', 'requires_serial_tracking', 'is_active', 'notes',
    ];

    protected $casts = [
        'unit_cost'                => 'decimal:2',
        'is_reusable'              => 'boolean',
        'requires_serial_tracking' => 'boolean',
        'is_active'                => 'boolean',
    ];

    public const CATEGORIES = [
        'instrument'  => 'Surgical Instrument',
        'implant'     => 'Implant',
        'disposable'  => 'Disposable',
        'suture'      => 'Suture',
        'drape'       => 'Drape/Linen',
        'other'       => 'Other',
    ];

    // ── Relationships ──

    public function stockIns(): HasMany
    {
        return $this->hasMany(OtConsumableStockIn::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(OtConsumableUsage::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBelowReorderLevel($query)
    {
        return $query->whereColumn('current_stock', '<=', 'reorder_level');
    }

    // ── Helpers ──

    public function needsReorder(): bool
    {
        return $this->current_stock <= $this->reorder_level;
    }

    /**
     * Consume stock using FIFO — deducts from oldest batches first.
     * Returns the stock_in_id used (for traceability).
     */
    public function consumeFifo(int $quantity): ?int
    {
        $remaining = $quantity;
        $firstBatchId = null;

        $batches = $this->stockIns()
            ->where('remaining_quantity', '>', 0)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $deduct = min($remaining, $batch->remaining_quantity);
            $batch->decrement('remaining_quantity', $deduct);
            $remaining -= $deduct;

            if (!$firstBatchId) {
                $firstBatchId = $batch->id;
            }
        }

        // Update current_stock
        $this->decrement('current_stock', $quantity - $remaining);

        return $firstBatchId;
    }
}
