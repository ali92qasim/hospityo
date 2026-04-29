<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Shift extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'name', 'code', 'start_time', 'end_time', 'break_duration',
        'working_hours', 'grace_minutes', 'color', 'is_overnight', 'is_active',
    ];

    protected $casts = [
        'break_duration' => 'decimal:2',
        'working_hours' => 'decimal:2',
        'is_overnight' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function dutyRosters(): HasMany
    {
        return $this->hasMany(DutyRoster::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /**
     * Get formatted time range.
     */
    public function getTimeRangeAttribute(): string
    {
        return \Carbon\Carbon::parse($this->start_time)->format('h:i A') . ' — ' .
               \Carbon\Carbon::parse($this->end_time)->format('h:i A');
    }
}
