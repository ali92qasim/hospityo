<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

/**
 * DoctorShareItem — earned share liability record.
 *
 * One row per bill_item where a doctor share was calculated.
 * Defines what the hospital owes the doctor for a specific billed item.
 *
 * This table does NOT track collection. Collection is tracked in the
 * immutable event ledger: doctor_share_allocations.
 *
 * To get the total collected for this item:
 *   $item->allocations()->sum('amount')
 *   or use the static helper: DoctorShareItem::totalCollected($id)
 */
class DoctorShareItem extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'bill_id',
        'bill_item_id',
        'doctor_id',
        'rule_id',
        'rule_snapshot',
        'base_amount',
        'share_amount',
        'status',
        'void_reason',
        'voided_at',
        'settlement_id',
    ];

    protected $casts = [
        'rule_snapshot' => 'array',
        'base_amount'   => 'decimal:2',
        'share_amount'  => 'decimal:2',
        'voided_at'     => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function billItem(): BelongsTo
    {
        return $this->belongsTo(BillItem::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(DoctorShareRule::class, 'rule_id');
    }

    /**
     * All allocation events for this share item.
     * SUM(amount) = total collected (positive) or reversed (negative).
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(DoctorShareAllocation::class, 'doctor_share_item_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForBill(Builder $q, int $billId): Builder
    {
        return $q->where('bill_id', $billId);
    }

    public function scopeForDoctor(Builder $q, int $doctorId): Builder
    {
        return $q->where('doctor_id', $doctorId);
    }

    /**
     * Non-voided items — the financially active set.
     */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', '!=', 'voided');
    }

    /**
     * Items eligible for inclusion in a settlement run.
     * Status 'pending' means calculated but not yet settled.
     * The settlement run decides eligibility based on allocation totals,
     * not on a status field — this scope is the starting filter only.
     */
    public function scopeSettleable(Builder $q): Builder
    {
        return $q->where('status', 'pending')
                 ->whereNull('settlement_id');
    }
}
