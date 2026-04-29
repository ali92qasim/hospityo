<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class LeaveRequest extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'employee_id', 'leave_type_id', 'start_date', 'end_date', 'total_days',
        'is_half_day', 'half_day_type', 'reason', 'document_path',
        'status', 'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'decimal:1',
        'is_half_day' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', 'pending');
    }

    public function scopeApproved(Builder $q): Builder
    {
        return $q->where('status', 'approved');
    }

    /**
     * Calculate total days between start and end date (excluding weekends optionally).
     */
    public static function calculateDays(\Carbon\Carbon $start, \Carbon\Carbon $end, bool $isHalfDay = false): float
    {
        if ($isHalfDay) return 0.5;
        return $start->diffInDays($end) + 1;
    }
}
