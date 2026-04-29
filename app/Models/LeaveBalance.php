<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class LeaveBalance extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'employee_id', 'leave_type_id', 'year',
        'entitled_days', 'used_days', 'carried_forward',
    ];

    protected $casts = [
        'entitled_days' => 'decimal:1',
        'used_days' => 'decimal:1',
        'carried_forward' => 'decimal:1',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function getRemainingAttribute(): float
    {
        return $this->entitled_days + $this->carried_forward - $this->used_days;
    }

    /**
     * Get or create balance for an employee/leave type/year.
     */
    public static function getOrCreate(int $employeeId, int $leaveTypeId, ?int $year = null): self
    {
        $year = $year ?? date('Y');
        $leaveType = LeaveType::find($leaveTypeId);

        return static::firstOrCreate(
            ['employee_id' => $employeeId, 'leave_type_id' => $leaveTypeId, 'year' => $year],
            ['entitled_days' => $leaveType?->default_days ?? 0, 'used_days' => 0, 'carried_forward' => 0]
        );
    }
}
