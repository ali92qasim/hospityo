<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class OperationTheatre extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'name', 'type', 'status', 'floor', 'equipment', 'is_active', 'notes',
    ];

    protected $casts = [
        'equipment' => 'array',
        'is_active' => 'boolean',
    ];

    public function surgeries(): HasMany
    {
        return $this->hasMany(Surgery::class, 'operation_theatre_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeAvailable(Builder $q): Builder
    {
        return $q->where('status', 'available')->where('is_active', true);
    }

    public function isAvailableOn(string $date, ?string $startTime = null, ?string $endTime = null): bool
    {
        $query = $this->surgeries()
            ->where('scheduled_date', $date)
            ->whereIn('status', ['scheduled', 'in_progress']);

        if ($startTime && $endTime) {
            $query->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('scheduled_start_time', [$startTime, $endTime])
                  ->orWhereBetween('scheduled_end_time', [$startTime, $endTime]);
            });
        }

        return $query->count() === 0;
    }
}
