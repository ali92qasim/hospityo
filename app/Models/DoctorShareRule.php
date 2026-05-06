<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class DoctorShareRule extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'doctor_id',
        'service_id',
        'investigation_id',
        'share_type',
        'share_value',
        'applies_to',
        'is_active',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'share_value' => 'decimal:2',
        'is_active'   => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shareItems(): HasMany
    {
        return $this->hasMany(DoctorShareItem::class, 'rule_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /**
     * Rules that apply to a given bill type or to 'all'.
     */
    public function scopeForBillType(Builder $q, string $billType): Builder
    {
        return $q->where(function (Builder $sub) use ($billType) {
            $sub->where('applies_to', $billType)
                ->orWhere('applies_to', 'all');
        });
    }
}
