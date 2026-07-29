<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class IpdCareTeam extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'ipd_care_team';

    protected $fillable = [
        'visit_id',
        'doctor_id',
        'is_primary',
        'added_at',
        'added_by',
        'removed_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'added_at'   => 'datetime',
        'removed_at' => 'datetime',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('removed_at');
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }
}
