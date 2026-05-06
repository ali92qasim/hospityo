<?php

use App\Models\Attendance;
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
});

it('can mark attendance for an employee', function () {
    $attendance = Attendance::create([
        'employee_id' => $this->employee->id,
        'date' => '2026-04-15',
        'check_in' => '08:00',
        'check_out' => '17:00',
        'shift' => 'morning',
        'status' => 'present',
        'marked_by' => $this->user->id,
    ]);

    expect($attendance)->toBeInstanceOf(Attendance::class)
        ->and($attendance->status)->toBe('present')
        ->and($attendance->employee->full_name)->toBe('Ahmed Khan');
});

it('calculates worked hours from check-in and check-out', function () {
    $attendance = Attendance::create([
        'employee_id' => $this->employee->id,
        'date' => '2026-04-15',
        'check_in' => '08:00',
        'check_out' => '17:00',
        'status' => 'present',
    ]);

    $attendance->calculateWorkedHours();

    expect((float) $attendance->worked_hours)->toBe(9.0);
});

it('handles overnight shift calculation', function () {
    $attendance = Attendance::create([
        'employee_id' => $this->employee->id,
        'date' => '2026-04-15',
        'check_in' => '22:00',
        'check_out' => '06:00',
        'status' => 'present',
    ]);

    $attendance->calculateWorkedHours();

    expect((float) $attendance->worked_hours)->toBe(8.0);
});

it('enforces unique employee-date constraint', function () {
    Attendance::create([
        'employee_id' => $this->employee->id,
        'date' => '2026-04-15',
        'status' => 'present',
    ]);

    expect(fn() => Attendance::create([
        'employee_id' => $this->employee->id,
        'date' => '2026-04-15',
        'status' => 'absent',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('filters by date scope', function () {
    Attendance::create(['employee_id' => $this->employee->id, 'date' => '2026-04-15', 'status' => 'present']);
    Attendance::create(['employee_id' => $this->employee->id, 'date' => '2026-04-16', 'status' => 'present']);

    expect(Attendance::forDate('2026-04-15')->count())->toBe(1);
});

it('filters by month scope', function () {
    Attendance::create(['employee_id' => $this->employee->id, 'date' => '2026-04-15', 'status' => 'present']);
    Attendance::create(['employee_id' => $this->employee->id, 'date' => '2026-04-20', 'status' => 'present']);
    Attendance::create(['employee_id' => $this->employee->id, 'date' => '2026-03-15', 'status' => 'present']);

    expect(Attendance::forMonth(2026, 4)->count())->toBe(2)
        ->and(Attendance::forMonth(2026, 3)->count())->toBe(1);
});

it('tracks overtime hours', function () {
    $attendance = Attendance::create([
        'employee_id' => $this->employee->id,
        'date' => '2026-04-15',
        'check_in' => '08:00',
        'check_out' => '19:00',
        'status' => 'present',
        'overtime_hours' => 2,
    ]);

    expect((float) $attendance->overtime_hours)->toBe(2.0);
});
