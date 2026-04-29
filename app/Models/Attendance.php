<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Attendance extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'employee_id', 'date', 'check_in', 'check_out', 'shift',
        'status', 'worked_hours', 'overtime_hours', 'notes', 'marked_by',
    ];

    protected $casts = [
        'date' => 'date',
        'worked_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    /**
     * Calculate worked hours from check_in and check_out.
     */
    public function calculateWorkedHours(): void
    {
        if ($this->check_in && $this->check_out) {
            $in = \Carbon\Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->check_in);
            $out = \Carbon\Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->check_out);
            if ($out->lt($in)) $out->addDay(); // night shift
            $this->worked_hours = round($in->diffInMinutes($out) / 60, 2);
        }
    }

    public function scopeForDate(Builder $q, string $date): Builder
    {
        return $q->where('date', $date);
    }

    public function scopeForMonth(Builder $q, int $year, int $month): Builder
    {
        return $q->whereYear('date', $year)->whereMonth('date', $month);
    }
}
