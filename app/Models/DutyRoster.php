<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class DutyRoster extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'employee_id', 'shift_id', 'date', 'is_off_day', 'notes', 'assigned_by',
    ];

    protected $attributes = [
        'is_off_day' => false,
    ];

    protected $casts = ['date' => 'date', 'is_off_day' => 'boolean'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeForDate(Builder $q, string $date): Builder
    {
        return $q->whereDate('date', $date);
    }

    public function scopeForWeek(Builder $q, string $startDate, string $endDate): Builder
    {
        return $q->whereBetween('date', [$startDate, $endDate]);
    }
}
