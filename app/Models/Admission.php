<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Admission extends Model
{
    use HasFactory, Auditable, UsesTenantConnection;

    protected $fillable = [
        'visit_id',
        'bed_id',
        'admission_date',
        'discharge_date',
        'status',
        'admission_notes',
        'discharge_notes',
        'discharge_summary',
        'refund_amount',
        'refund_method',
        'refunded_at',
        'refunded_by',
    ];

    protected $casts = [
        'admission_date' => 'datetime',
        'discharge_date' => 'datetime',
        'refund_amount' => 'decimal:2',
        'refunded_at' => 'datetime',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    public function advances(): HasMany
    {
        return $this->hasMany(AdmissionAdvance::class);
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    public function getTotalAdvancesAttribute(): float
    {
        if ($this->relationLoaded('advances')) {
            return (float) $this->advances->sum('amount');
        }

        return (float) $this->advances()->sum('amount');
    }

    public function getDraftBillChargesAttribute(): float
    {
        if (! $this->visit_id) {
            return 0.0;
        }

        $visit = $this->relationLoaded('visit')
            ? $this->visit
            : Visit::find($this->visit_id);

        if (! $visit) {
            return 0.0;
        }

        $bill = \App\Services\IpdDraftBillService::resolveForVisit($visit);

        return (float) ($bill?->total_amount ?? 0);
    }

    /**
     * Running available credit during the stay: advances − current draft charges.
     * Positive = credit remaining, negative = amount still due.
     */
    public function getCreditBalanceAttribute(): float
    {
        return round($this->total_advances - $this->draft_bill_charges, 2);
    }
}
