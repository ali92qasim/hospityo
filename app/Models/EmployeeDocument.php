<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class EmployeeDocument extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'employee_id', 'title', 'document_type', 'document_number',
        'file_path', 'issue_date', 'expiry_date', 'issuing_authority',
        'notes', 'is_mandatory', 'is_verified', 'verified_by', 'verified_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
        'is_mandatory' => 'boolean',
        'is_verified' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ── Scopes ──

    public function scopeExpired(Builder $q): Builder
    {
        return $q->whereNotNull('expiry_date')->where('expiry_date', '<', now());
    }

    public function scopeExpiringSoon(Builder $q, int $days = 30): Builder
    {
        return $q->whereNotNull('expiry_date')
            ->where('expiry_date', '>=', now())
            ->where('expiry_date', '<=', now()->addDays($days));
    }

    public function scopeMandatory(Builder $q): Builder
    {
        return $q->where('is_mandatory', true);
    }

    public function scopeUnverified(Builder $q): Builder
    {
        return $q->where('is_verified', false);
    }

    // ── Helpers ──

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date && $this->expiry_date->isFuture() && $this->expiry_date->diffInDays(now()) <= $days;
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) return null;
        return (int) now()->diffInDays($this->expiry_date, false);
    }
}
