<?php

use App\Models\Account;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use App\Services\EmployeeAccountService;
beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->department = Department::create([
        'name' => 'Emergency',
        'description' => 'Emergency Department',
        'status' => 'active',
    ]);

    $this->designation = Designation::create([
        'name' => 'Consultant',
        'category' => 'medical',
        'is_active' => true,
    ]);
});

it('auto-generates employee number on creation', function () {
    $employee = Employee::create([
        'name' => 'Ahmed Khan',
        'department_id' => $this->department->id,
        'designation_id' => $this->designation->id,
        'joining_date' => '2026-01-15',
        'basic_salary' => 80000,
    ]);

    expect($employee->employee_no)->toStartWith('EMP')
        ->and(strlen($employee->employee_no))->toBe(8); // EMP + 5 digits
});

it('computes full name accessor from name column', function () {
    $employee = Employee::create([
        'name' => 'Ahmed Khan',
        'department_id' => $this->department->id,
        'joining_date' => '2026-01-15',
        'basic_salary' => 50000,
    ]);

    expect($employee->full_name)->toBe('Ahmed Khan')
        ->and($employee->initials)->toBe('AK');
});

it('filters active employees', function () {
    Employee::create([
        'name' => 'Active Employee',
        'department_id' => $this->department->id,
        'joining_date' => '2026-01-01',
        'status' => 'active',
        'basic_salary' => 50000,
    ]);

    Employee::create([
        'name' => 'Terminated Employee',
        'department_id' => $this->department->id,
        'joining_date' => '2025-01-01',
        'status' => 'terminated',
        'basic_salary' => 40000,
    ]);

    expect(Employee::active()->count())->toBe(1);
});

it('filters by department', function () {
    $dept2 = Department::create(['name' => 'Cardiology', 'status' => 'active']);

    Employee::create([
        'name' => 'Emp1 A',
        'department_id' => $this->department->id,
        'joining_date' => '2026-01-01',
        'basic_salary' => 50000,
    ]);

    Employee::create([
        'name' => 'Emp2 B',
        'department_id' => $dept2->id,
        'joining_date' => '2026-01-01',
        'basic_salary' => 50000,
    ]);

    expect(Employee::byDepartment($this->department->id)->count())->toBe(1)
        ->and(Employee::byDepartment($dept2->id)->count())->toBe(1);
});

it('filters by employment type', function () {
    Employee::create([
        'name' => 'Full Time',
        'department_id' => $this->department->id,
        'joining_date' => '2026-01-01',
        'employment_type' => 'full_time',
        'basic_salary' => 50000,
    ]);

    Employee::create([
        'name' => 'Part Time',
        'department_id' => $this->department->id,
        'joining_date' => '2026-01-01',
        'employment_type' => 'part_time',
        'basic_salary' => 30000,
    ]);

    expect(Employee::byType('full_time')->count())->toBe(1)
        ->and(Employee::byType('part_time')->count())->toBe(1);
});

it('belongs to department and designation', function () {
    $employee = Employee::create([
        'name' => 'Test Employee',
        'department_id' => $this->department->id,
        'designation_id' => $this->designation->id,
        'joining_date' => '2026-01-01',
        'basic_salary' => 60000,
    ]);

    expect($employee->department->name)->toBe('Emergency')
        ->and($employee->designation->name)->toBe('Consultant');
});

it('creates a linked salary expense account on creation', function () {
    Account::create(['code' => '5300', 'name' => 'Salaries & Wages', 'type' => 'expense', 'is_system' => false]);

    $employee = Employee::create([
        'name' => 'Sara Ali',
        'department_id' => $this->department->id,
        'joining_date' => '2026-01-01',
        'basic_salary' => 70000,
    ]);

    $employee->refresh();

    expect($employee->expense_account_id)->not->toBeNull()
        ->and($employee->expenseAccount)->not->toBeNull()
        ->and($employee->expenseAccount->code)->toBe(EmployeeAccountService::expenseAccountCode($employee->id))
        ->and($employee->expenseAccount->type)->toBe('expense')
        ->and($employee->expenseAccount->parent_id)->toBe(Account::where('code', '5300')->value('id'));
});
