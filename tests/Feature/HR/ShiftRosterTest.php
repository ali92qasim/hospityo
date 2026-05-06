<?php

use App\Models\Shift;
use App\Models\DutyRoster;
use App\Models\ShiftSwapRequest;
use App\Models\Employee;
use App\Models\Department;
use App\Models\User;
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

    $this->morningShift = Shift::create([
        'name' => 'Morning',
        'code' => 'MOR',
        'start_time' => '08:00',
        'end_time' => '16:00',
        'working_hours' => 8,
        'grace_minutes' => 15,
        'color' => '#10B981',
        'is_overnight' => false,
        'is_active' => true,
    ]);

    $this->nightShift = Shift::create([
        'name' => 'Night',
        'code' => 'NGT',
        'start_time' => '22:00',
        'end_time' => '06:00',
        'working_hours' => 8,
        'grace_minutes' => 15,
        'color' => '#6366F1',
        'is_overnight' => true,
        'is_active' => true,
    ]);
});

it('can create a shift definition', function () {
    expect($this->morningShift->name)->toBe('Morning')
        ->and($this->morningShift->is_overnight)->toBeFalse()
        ->and($this->nightShift->is_overnight)->toBeTrue();
});

it('formats time range accessor', function () {
    expect($this->morningShift->time_range)->toBe('08:00 AM — 04:00 PM');
});

it('filters active shifts', function () {
    Shift::create([
        'name' => 'Inactive',
        'code' => 'INA',
        'start_time' => '10:00',
        'end_time' => '18:00',
        'working_hours' => 8,
        'is_active' => false,
    ]);

    expect(Shift::active()->count())->toBe(2); // morning + night
});

it('can assign duty roster to employee', function () {
    $roster = DutyRoster::create([
        'employee_id' => $this->employee->id,
        'shift_id' => $this->morningShift->id,
        'date' => '2026-04-15',
        'assigned_by' => $this->user->id,
    ]);

    expect($roster->employee->full_name)->toBe('Ahmed Khan')
        ->and($roster->shift->name)->toBe('Morning')
        ->and($roster->is_off_day)->toBeFalse();
});

it('can mark off day in roster', function () {
    $roster = DutyRoster::create([
        'employee_id' => $this->employee->id,
        'shift_id' => $this->morningShift->id,
        'date' => '2026-04-18',
        'is_off_day' => true,
        'assigned_by' => $this->user->id,
    ]);

    expect($roster->is_off_day)->toBeTrue();
});

it('enforces unique employee-date in roster', function () {
    DutyRoster::create([
        'employee_id' => $this->employee->id,
        'shift_id' => $this->morningShift->id,
        'date' => '2026-04-15',
    ]);

    expect(fn() => DutyRoster::create([
        'employee_id' => $this->employee->id,
        'shift_id' => $this->nightShift->id,
        'date' => '2026-04-15',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('filters roster by date', function () {
    DutyRoster::create(['employee_id' => $this->employee->id, 'shift_id' => $this->morningShift->id, 'date' => '2026-04-15']);
    DutyRoster::create(['employee_id' => $this->employee->id, 'shift_id' => $this->morningShift->id, 'date' => '2026-04-16']);

    expect(DutyRoster::forDate('2026-04-15')->count())->toBe(1);
});

it('filters roster by week', function () {
    DutyRoster::create(['employee_id' => $this->employee->id, 'shift_id' => $this->morningShift->id, 'date' => '2026-04-14']);
    DutyRoster::create(['employee_id' => $this->employee->id, 'shift_id' => $this->morningShift->id, 'date' => '2026-04-16']);
    DutyRoster::create(['employee_id' => $this->employee->id, 'shift_id' => $this->morningShift->id, 'date' => '2026-04-21']);

    expect(DutyRoster::forWeek('2026-04-14', '2026-04-18')->count())->toBe(2);
});

it('can create shift swap request', function () {
    $employee2 = Employee::create([
        'first_name' => 'Sara',
        'last_name' => 'Ali',
        'department_id' => $this->department->id,
        'joining_date' => '2026-01-01',
        'basic_salary' => 55000,
        'status' => 'active',
    ]);

    $swap = ShiftSwapRequest::create([
        'requester_id' => $this->employee->id,
        'target_id' => $employee2->id,
        'swap_date' => '2026-04-20',
        'requester_shift_id' => $this->morningShift->id,
        'target_shift_id' => $this->nightShift->id,
        'reason' => 'Personal commitment',
        'status' => 'pending',
    ]);

    expect($swap->requester->full_name)->toBe('Ahmed Khan')
        ->and($swap->target->full_name)->toBe('Sara Ali')
        ->and($swap->status)->toBe('pending');
});
