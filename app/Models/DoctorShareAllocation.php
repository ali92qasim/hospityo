<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

/**
 * DoctorShareAllocation — immutable event ledger entry.
 *
 * One row per payment event that allocates collected cash toward a share item.
 * Rows are NEVER updated or deleted after insert.
 *
 * Positive amount = cash collected (payment received).
 * Negative amount = reversal (refund issued against a prior payment).
 *
 * The running balance for any share item is:
 *   SELECT SUM(amount) FROM doctor_share_allocations
 *   WHERE doctor_share_item_id = ?
 *
 * The running balance at any point in time is:
 *   SELECT SUM(amount) FROM doctor_share_allocations
 *   WHERE doctor_share_item_id = ? AND created_at <= ?
 */
class DoctorShareAllocation extends Model
{
    use UsesTenantConnection;

    /**
     * No updated_at — this model is immutable after creation.
     */
    public $timestamps = false;

    protected $fillable = [
        'doctor_share_item_id',
        'payment_id',
        'bill_id',
        'doctor_id',
        'amount',
        'type',
        'notes',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'created_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function shareItem(): BelongsTo
    {
        return $this->belongsTo(DoctorShareItem::class, 'doctor_share_item_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    // ── Boot ──────────────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        // Enforce immutability — allocations are never updated
        static::updating(function () {
            throw new \LogicException(
                'DoctorShareAllocation records are immutable and cannot be updated. ' .
                'Create a reversal entry instead.'
            );
        });

        // Enforce immutability — allocations are never deleted
        static::deleting(function () {
            throw new \LogicException(
                'DoctorShareAllocation records are immutable and cannot be deleted. ' .
                'Create a reversal entry instead.'
            );
        });

        // Set created_at on insert since $timestamps = false
        static::creating(function (self $allocation) {
            $allocation->created_at = now();
        });
    }
}
