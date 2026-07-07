<?php

use App\Models\Account;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Department;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\PayrollService;
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

    Account::create(['code' => '5300', 'name' => 'Salaries & Wages', 'type' => 'expense', 'is_system' => false]);
    Account::create(['code' => '1100', 'name' => 'Cash in Hand', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '2200', 'name' => 'Accounts Payable', 'type' => 'liability', 'is_system' => true]);

    $this->employee = Employee::create([
        'first_name' => 'Ahmed',
        'last_name' => 'Khan',
        'department_id' => $this->department->id,
        'joining_date' => '2026-01-01',
        'basic_salary' => 60000,
        'status' => 'active',
    ]);

    // Seed salary components
    SalaryComponent::create([
        'name' => 'House Rent Allowance',
        'code' => 'HRA',
        'type' => 'allowance',
        'calculation' => 'percentage',
        'default_amount' => 45,
        'percentage_of' => 'basic_salary',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    SalaryComponent::create([
        'name' => 'Medical Allowance',
        'code' => 'MED',
        'type' => 'allowance',
        'calculation' => 'fixed',
        'default_amount' => 5000,
        'is_active' => true,
        'sort_order' => 2,
    ]);

    SalaryComponent::create([
        'name' => 'EOBI',
        'code' => 'EOBI',
        'type' => 'deduction',
        'calculation' => 'percentage',
        'default_amount' => 1,
        'percentage_of' => 'basic_salary',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    // Mark attendance for April 2026 (all working days present)
    $daysInMonth = 30;
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $date = \Carbon\Carbon::create(2026, 4, $d);
        if ($date->isSunday()) continue;

        Attendance::create([
            'employee_id' => $this->employee->id,
            'date' => $date->format('Y-m-d'),
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
            'worked_hours' => 9,
        ]);
    }
});

it('generates payroll run for a month', function () {
    $run = PayrollService::generate(2026, 4, $this->user->id);

    expect($run)->toBeInstanceOf(PayrollRun::class)
        ->and($run->year)->toBe(2026)
        ->and($run->month)->toBe(4)
        ->and($run->status)->toBe('draft')
        ->and($run->total_employees)->toBe(1);
});

it('generates payslip with correct basic salary', function () {
    $run = PayrollService::generate(2026, 4, $this->user->id);
    $payslip = $run->payslips->first();

    expect($payslip)->toBeInstanceOf(Payslip::class)
        ->and((float) $payslip->basic_salary)->toBe(60000.0)
        ->and($payslip->absent_days)->toBe(0);
});

it('calculates allowances correctly', function () {
    $run = PayrollService::generate(2026, 4, $this->user->id);
    $payslip = $run->payslips->first();

    // HRA = 45% of 60000 = 27000
    // Medical = 5000 fixed
    // Total allowances = 32000
    expect((float) $payslip->total_allowances)->toBe(32000.0);
});

it('calculates deductions correctly', function () {
    $run = PayrollService::generate(2026, 4, $this->user->id);
    $payslip = $run->payslips->first();

    // EOBI = 1% of 60000 = 600
    expect((float) $payslip->total_deductions)->toBe(600.0);
});

it('calculates net salary correctly', function () {
    $run = PayrollService::generate(2026, 4, $this->user->id);
    $payslip = $run->payslips->first();

    // Gross = 60000 + 27000 + 5000 = 92000
    // Net = 92000 - 600 = 91400
    expect((float) $payslip->gross_salary)->toBe(92000.0)
        ->and((float) $payslip->net_salary)->toBe(91400.0);
});

it('pro-rates salary for absent days', function () {
    // Mark 2 days as absent (overwrite existing)
    $workingDays = Attendance::where('employee_id', $this->employee->id)->count();

    // Delete last 2 attendance records and mark as absent
    $lastTwo = Attendance::where('employee_id', $this->employee->id)
        ->orderBy('date', 'desc')->take(2)->get();

    foreach ($lastTwo as $att) {
        $att->update(['status' => 'absent', 'check_in' => null, 'check_out' => null, 'worked_hours' => 0]);
    }

    $run = PayrollService::generate(2026, 4, $this->user->id);
    $payslip = $run->payslips->first();

    expect($payslip->absent_days)->toBe(2)
        ->and((float) $payslip->absent_deduction)->toBeGreaterThan(0)
        ->and((float) $payslip->basic_salary)->toBeLessThan(60000);
});

it('calculates overtime at 1.5x rate', function () {
    // Add overtime to some attendance records
    Attendance::where('employee_id', $this->employee->id)
        ->limit(5)
        ->update(['overtime_hours' => 2]);

    $run = PayrollService::generate(2026, 4, $this->user->id);
    $payslip = $run->payslips->first();

    expect((float) $payslip->overtime_hours)->toBe(10.0)
        ->and((float) $payslip->overtime_amount)->toBeGreaterThan(0);
});

it('posts payroll to accounting using employee expense accounts', function () {
    $run = PayrollService::generate(2026, 4, $this->user->id);
    $run->update(['status' => 'completed']);

    PayrollService::postToAccounting($run);

    $entry = \App\Models\JournalEntry::where('reference_type', 'PayrollRun')
        ->where('reference_id', $run->id)
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->is_auto)->toBeTrue()
        ->and($entry->lines->sum('debit'))->toEqual($entry->lines->sum('credit'));

    $this->employee->refresh();

    $expenseLine = $entry->lines()
        ->where('account_id', $this->employee->expense_account_id)
        ->where('debit', '>', 0)
        ->first();

    expect($expenseLine)->not->toBeNull()
        ->and((float) $expenseLine->debit)->toBe(92000.0);
});

it('recalculates payroll run totals', function () {
    $run = PayrollService::generate(2026, 4, $this->user->id);

    expect((float) $run->total_gross)->toBeGreaterThan(0)
        ->and((float) $run->total_net)->toBeGreaterThan(0)
        ->and((float) $run->total_deductions)->toBeGreaterThan(0)
        ->and($run->total_employees)->toBe(1);
});
