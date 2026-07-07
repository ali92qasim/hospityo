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
        'first_name' => 'Ahmed',
        'last_name' => 'Khan',
        'department_id' => $this->department->id,
        'designation_id' => $this->designation->id,
        'joining_date' => '2026-01-15',
        'basic_salary' => 80000,
    ]);

    expect($employee->employee_no)->toStartWith('EMP')
        ->and(strlen($employee->employee_no))->toBe(8); // EMP + 5 digits
});

it('computes full name accessor', function () {
    $employee = Employee::create([
        'first_name' => 'Ahmed',
        'last_name' => 'Khan',
        'department_id' => $this->department->id,
        'joining_date' => '2026-01-15',
        'basic_salary' => 50000,
    ]);

    expect($employee->full_name)->toBe('Ahmed Khan');
});

it('filters active employees', function () {
    Employee::create([
        'first_name' => 'Active',
        'last_name' => 'Employee',
        'department_id' => $this->department->id,
        'joining_date' => '2026-01-01',
        'status' => 'active',
        'basic_salary' => 50000,
    ]);

    Employee::create([
        'first_name' => 'Terminated',
        'last_name' => 'Employee',
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
        'first_name' => 'Emp1',
        'last_name' => 'A',
        'department_id' => $this->department->id,
        'joining_date' => '2026-01-01',
        'basic_salary' => 50000,
    ]);

    Employee::create([
        'first_name' => 'Emp2',
        'last_name' => 'B',
        'department_id' => $dept2->id,
        'joining_date' => '2026-01-01',
        'basic_salary' => 50000,
    ]);

    expect(Employee::byDepartment($this->department->id)->count())->toBe(1)
        ->and(Employee::byDepartment($dept2->id)->count())->toBe(1);
});

it('filters by employment type', function () {
    Employee::create([
        'first_name' => 'Full',
        'last_name' => 'Time',
        'department_id' => $this->department->id,
        'joining_date' => '2026-01-01',
        'employment_type' => 'full_time',
        'basic_salary' => 50000,
    ]);

    Employee::create([
        'first_name' => 'Part',
        'last_name' => 'Time',
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
        'first_name' => 'Test',
        'last_name' => 'Employee',
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
        'first_name' => 'Sara',
        'last_name' => 'Ali',
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
