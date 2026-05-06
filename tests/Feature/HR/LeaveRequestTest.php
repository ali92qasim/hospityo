<?php

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\Employee;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->department = Department::create([
        'name' => 'Emergency',
        'status' => 'active',
    ]);

    $this->employee = Employee::create([
        'first_name' => 'Ahmed',
        'last_name' => 'Khan',
        'department_id' => $this->department->id,
        'joining_date' => '2026-01-01',
        'basic_salary' => 60000,
        'status' => 'active',
    ]);

    $this->leaveType = LeaveType::create([
        'name' => 'Annual Leave',
        'code' => 'AL',
        'default_days' => 14,
        'is_paid' => true,
        'is_carry_forward' => true,
        'max_carry_forward_days' => 5,
        'is_active' => true,
    ]);
});

it('can create a leave request', function () {
    $request = LeaveRequest::create([
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'start_date' => '2026-04-20',
        'end_date' => '2026-04-22',
        'total_days' => 3,
        'reason' => 'Family event',
        'status' => 'pending',
    ]);

    expect($request)->toBeInstanceOf(LeaveRequest::class)
        ->and($request->status)->toBe('pending')
        ->and((float) $request->total_days)->toBe(3.0);
});

it('calculates total days correctly', function () {
    $start = Carbon::parse('2026-04-20');
    $end = Carbon::parse('2026-04-24');

    $days = LeaveRequest::calculateDays($start, $end);
    expect($days)->toBe(5.0);
});

it('returns 0.5 for half day leave', function () {
    $start = Carbon::parse('2026-04-20');
    $end = Carbon::parse('2026-04-20');

    $days = LeaveRequest::calculateDays($start, $end, true);
    expect($days)->toBe(0.5);
});

it('filters pending requests', function () {
    LeaveRequest::create([
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'start_date' => '2026-04-20',
        'end_date' => '2026-04-20',
        'total_days' => 1,
        'reason' => 'Sick',
        'status' => 'pending',
    ]);

    LeaveRequest::create([
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'start_date' => '2026-04-25',
        'end_date' => '2026-04-25',
        'total_days' => 1,
        'reason' => 'Personal',
        'status' => 'approved',
        'approved_by' => $this->user->id,
        'approved_at' => now(),
    ]);

    expect(LeaveRequest::pending()->count())->toBe(1)
        ->and(LeaveRequest::approved()->count())->toBe(1);
});

it('creates leave balance with defaults from leave type', function () {
    $balance = LeaveBalance::getOrCreate($this->employee->id, $this->leaveType->id, 2026);

    expect($balance)->toBeInstanceOf(LeaveBalance::class)
        ->and((float) $balance->entitled_days)->toBe(14.0)
        ->and((float) $balance->used_days)->toBe(0.0)
        ->and($balance->remaining)->toBe(14.0);
});

it('calculates remaining leave balance', function () {
    $balance = LeaveBalance::create([
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'year' => 2026,
        'entitled_days' => 14,
        'used_days' => 5,
        'carried_forward' => 3,
    ]);

    // remaining = entitled + carried_forward - used = 14 + 3 - 5 = 12
    expect($balance->remaining)->toBe(12.0);
});

it('supports half day leave requests', function () {
    $request = LeaveRequest::create([
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'start_date' => '2026-04-20',
        'end_date' => '2026-04-20',
        'total_days' => 0.5,
        'is_half_day' => true,
        'half_day_type' => 'first_half',
        'reason' => 'Doctor appointment',
        'status' => 'pending',
    ]);

    expect($request->is_half_day)->toBeTrue()
        ->and($request->half_day_type)->toBe('first_half')
        ->and((float) $request->total_days)->toBe(0.5);
});
