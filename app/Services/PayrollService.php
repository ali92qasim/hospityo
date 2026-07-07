<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\SalaryComponent;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Account;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollService
{
    /**
     * Generate payroll for a given month/year.
     */
    public static function generate(int $year, int $month, int $createdBy): PayrollRun
    {
        $monthName = Carbon::create($year, $month)->format('F Y');

        $run = PayrollRun::create([
            'title' => "Payroll — {$monthName}",
            'year' => $year,
            'month' => $month,
            'status' => 'processing',
            'created_by' => $createdBy,
        ]);

        $employees = Employee::active()->with(['department'])->get();
        $allowances = SalaryComponent::active()->allowances()->orderBy('sort_order')->get();
        $deductions = SalaryComponent::active()->deductions()->orderBy('sort_order')->get();
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;

        // Working days (exclude Sundays — Pakistan standard)
        $workingDays = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $day = Carbon::create($year, $month, $d);
            if (!$day->isSunday()) $workingDays++;
        }

        foreach ($employees as $employee) {
            try {
                self::generatePayslip($run, $employee, $year, $month, $workingDays, $allowances, $deductions);
            } catch (\Throwable $e) {
                Log::error('[Payroll] Payslip failed', ['employee' => $employee->id, 'error' => $e->getMessage()]);
            }
        }

        $run->recalculateTotals();
        $run->update(['status' => 'draft']);

        return $run;
    }

    /**
     * Generate a single payslip for an employee.
     */
    protected static function generatePayslip(
        PayrollRun $run, Employee $employee, int $year, int $month,
        int $workingDays, $allowances, $deductions
    ): Payslip {
        // ── Attendance Summary ──
        $attendances = Attendance::where('employee_id', $employee->id)
            ->forMonth($year, $month)->get();

        $presentDays = $attendances->whereIn('status', ['present', 'late'])->count();
        $absentDays = $attendances->where('status', 'absent')->count();
        $halfDays = $attendances->where('status', 'half_day')->count();
        $leaveDays = $attendances->where('status', 'on_leave')->count();
        $overtimeHours = $attendances->sum('overtime_hours');

        $effectivePresent = $presentDays + ($halfDays * 0.5) + $leaveDays; // paid leave counts

        // ── Basic Salary (pro-rated for absences) ──
        $basicSalary = (float) $employee->basic_salary;
        $perDaySalary = $workingDays > 0 ? $basicSalary / $workingDays : 0;
        $absentDeduction = round($perDaySalary * $absentDays, 2);
        $adjustedBasic = round($basicSalary - $absentDeduction, 2);

        // ── Employee-specific component overrides ──
        $empOverrides = DB::connection('tenant')->table('employee_salary_components')
            ->where('employee_id', $employee->id)
            ->pluck('amount', 'salary_component_id')
            ->toArray();

        // ── Allowances ──
        $earningsBreakdown = [['component' => 'Basic Salary', 'amount' => $adjustedBasic]];
        $totalAllowances = 0;

        foreach ($allowances as $comp) {
            $override = $empOverrides[$comp->id] ?? null;
            $amount = $comp->calculate($adjustedBasic, 0, $override);
            if ($amount > 0) {
                $earningsBreakdown[] = ['component' => $comp->name, 'amount' => $amount];
                $totalAllowances += $amount;
            }
        }

        $grossSalary = $adjustedBasic + $totalAllowances;

        // Overtime (1.5x hourly rate)
        $hourlyRate = $workingDays > 0 ? $basicSalary / ($workingDays * 8) : 0;
        $overtimeAmount = round($overtimeHours * $hourlyRate * 1.5, 2);
        if ($overtimeAmount > 0) {
            $earningsBreakdown[] = ['component' => 'Overtime', 'amount' => $overtimeAmount];
            $grossSalary += $overtimeAmount;
        }

        // ── Deductions ──
        $deductionsBreakdown = [];
        $totalDeductions = 0;

        foreach ($deductions as $comp) {
            $override = $empOverrides[$comp->id] ?? null;
            $amount = $comp->calculate($adjustedBasic, $grossSalary, $override);
            if ($amount > 0) {
                $deductionsBreakdown[] = ['component' => $comp->name, 'amount' => $amount];
                $totalDeductions += $amount;
            }
        }

        // Absent deduction as separate line
        if ($absentDeduction > 0) {
            $deductionsBreakdown[] = ['component' => 'Absent Deduction', 'amount' => $absentDeduction];
        }

        $netSalary = round($grossSalary - $totalDeductions, 2);

        return Payslip::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'working_days' => $workingDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'leave_days' => $leaveDays,
            'overtime_hours' => $overtimeHours,
            'basic_salary' => $adjustedBasic,
            'total_allowances' => $totalAllowances,
            'overtime_amount' => $overtimeAmount,
            'gross_salary' => $grossSalary,
            'total_deductions' => $totalDeductions,
            'tax_amount' => 0, // can be enhanced with income tax slabs
            'absent_deduction' => $absentDeduction,
            'loan_deduction' => 0,
            'net_salary' => $netSalary,
            'earnings_breakdown' => $earningsBreakdown,
            'deductions_breakdown' => $deductionsBreakdown,
        ]);
    }

    /**
     * Post payroll journal entries to accounting.
     */
    public static function postToAccounting(PayrollRun $run): void
    {
        try {
            $existing = JournalEntry::where('reference_type', 'PayrollRun')
                ->where('reference_id', $run->id)
                ->where('entry_type', 'original')
                ->first();

            if ($existing) {
                Log::info('[Payroll] Journal entry already exists — skipping', [
                    'payroll_run_id' => $run->id,
                    'entry_id' => $existing->id,
                ]);
                return;
            }

            $cashAccount = Account::where('code', '1100')->first();
            $payable = Account::where('code', '2200')->first();

            if (! $cashAccount) {
                Log::warning('[Payroll] Cash account not found, skipping journal entry');
                return;
            }

            $run->load('payslips.employee.expenseAccount');

            $entry = JournalEntry::create([
                'entry_date' => Carbon::create($run->year, $run->month)->endOfMonth(),
                'reference_type' => 'PayrollRun',
                'reference_id' => $run->id,
                'description' => "Salary expense — {$run->title}",
                'created_by' => $run->created_by,
                'is_auto' => true,
                'entry_type' => 'original',
            ]);

            foreach ($run->payslips as $payslip) {
                $gross = (float) $payslip->gross_salary;
                if ($gross <= 0) {
                    continue;
                }

                $employee = $payslip->employee;
                if (! $employee) {
                    continue;
                }

                $expenseAccount = EmployeeAccountService::resolveExpenseAccount($employee);
                if (! $expenseAccount) {
                    Log::warning('[Payroll] Missing expense account for employee', [
                        'employee_id' => $employee->id,
                        'payroll_run_id' => $run->id,
                    ]);
                    continue;
                }

                $entry->lines()->create([
                    'account_id' => $expenseAccount->id,
                    'debit' => $gross,
                    'credit' => 0,
                    'narration' => "Gross salary — {$employee->full_name} ({$run->title})",
                ]);
            }

            if ($entry->lines()->where('debit', '>', 0)->doesntExist()) {
                $entry->delete();
                Log::warning('[Payroll] No salary expense lines posted', ['payroll_run_id' => $run->id]);
                return;
            }

            // CR: Cash/Bank (net paid)
            $entry->lines()->create([
                'account_id' => $cashAccount->id,
                'debit' => 0,
                'credit' => $run->total_net,
                'narration' => "Net salary paid for {$run->title}",
            ]);

            // CR: Deductions payable (if any difference)
            $deductionsDiff = $run->total_gross - $run->total_net;
            if ($deductionsDiff > 0 && $payable) {
                $entry->lines()->create([
                    'account_id' => $payable->id,
                    'debit' => 0,
                    'credit' => $deductionsDiff,
                    'narration' => "Statutory deductions for {$run->title}",
                ]);
            }

            Log::info('[Payroll] Journal entry posted', ['entry' => $entry->entry_number]);
        } catch (\Throwable $e) {
            Log::error('[Payroll] Accounting post failed', ['error' => $e->getMessage()]);
        }
    }
}
