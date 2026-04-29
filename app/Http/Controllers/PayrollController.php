<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\SalaryComponent;
use App\Models\Employee;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollController extends Controller
{
    // ── Payroll Runs ──

    public function index()
    {
        $payrollRuns = PayrollRun::with('createdBy')->latest()->paginate(12);
        return view('admin.hr.payroll.index', compact('payrollRuns'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2050',
            'month' => 'required|integer|min:1|max:12',
        ]);

        // Check if already exists
        $existing = PayrollRun::where('year', $request->year)->where('month', $request->month)->first();
        if ($existing) {
            return back()->with('error', "Payroll for {$request->month}/{$request->year} already exists.");
        }

        try {
            $run = PayrollService::generate($request->year, $request->month, auth()->id());
            return redirect()->route('hr.payroll.show', $run)->with('success', "Payroll generated for {$run->total_employees} employees.");
        } catch (\Throwable $e) {
            Log::error('[Payroll] Generate failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to generate payroll: ' . $e->getMessage());
        }
    }

    public function show(PayrollRun $payrollRun)
    {
        $payrollRun->load(['payslips.employee.department', 'payslips.employee.designation', 'createdBy', 'approvedBy']);
        return view('admin.hr.payroll.show', compact('payrollRun'));
    }

    public function approve(PayrollRun $payrollRun)
    {
        if ($payrollRun->status !== 'draft') {
            return back()->with('error', 'Only draft payrolls can be approved.');
        }

        $payrollRun->update([
            'status' => 'completed',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Post to accounting
        PayrollService::postToAccounting($payrollRun);

        return back()->with('success', 'Payroll approved and posted to accounting.');
    }

    public function cancel(PayrollRun $payrollRun)
    {
        if ($payrollRun->status === 'completed') {
            return back()->with('error', 'Cannot cancel a completed payroll.');
        }

        $payrollRun->update(['status' => 'cancelled']);
        return back()->with('success', 'Payroll cancelled.');
    }

    // ── Payslips ──

    public function payslip(Payslip $payslip)
    {
        $payslip->load(['employee.department', 'employee.designation', 'payrollRun']);
        return view('admin.hr.payroll.payslip', compact('payslip'));
    }

    public function printPayslip(Payslip $payslip)
    {
        $payslip->load(['employee.department', 'employee.designation', 'payrollRun']);
        $settings = [
            'hospital_name' => setting('hospital_name', config('app.name')),
            'hospital_address' => setting('hospital_address', ''),
            'hospital_phone' => setting('hospital_phone', ''),
        ];
        return view('admin.hr.payroll.print-payslip', compact('payslip', 'settings'));
    }

    public function markPaid(Request $request, Payslip $payslip)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,bank_transfer,cheque',
            'payment_date' => 'required|date',
        ]);

        $payslip->update([
            'payment_status' => 'paid',
            'payment_method' => $request->payment_method,
            'payment_date' => $request->payment_date,
        ]);

        return back()->with('success', 'Payslip marked as paid.');
    }

    // ── Salary Components ──

    public function components()
    {
        $components = SalaryComponent::orderBy('type')->orderBy('sort_order')->get();
        return view('admin.hr.payroll.components', compact('components'));
    }

    public function createComponent()
    {
        return view('admin.hr.payroll.create-component');
    }

    public function storeComponent(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:tenant.salary_components,code',
            'type' => 'required|in:allowance,deduction',
            'calculation' => 'required|in:fixed,percentage',
            'default_amount' => 'required|numeric|min:0',
            'percentage_of' => 'nullable|in:basic_salary,gross_salary',
            'is_taxable' => 'nullable|boolean',
        ]);

        SalaryComponent::create([
            ...$request->only('name', 'code', 'type', 'calculation', 'default_amount', 'percentage_of'),
            'is_taxable' => $request->boolean('is_taxable', true),
        ]);

        return redirect()->route('hr.payroll.components')->with('success', 'Salary component created.');
    }

    public function editComponent(SalaryComponent $salaryComponent)
    {
        return view('admin.hr.payroll.edit-component', compact('salaryComponent'));
    }

    public function updateComponent(Request $request, SalaryComponent $salaryComponent)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:tenant.salary_components,code,' . $salaryComponent->id,
            'type' => 'required|in:allowance,deduction',
            'calculation' => 'required|in:fixed,percentage',
            'default_amount' => 'required|numeric|min:0',
            'percentage_of' => 'nullable|in:basic_salary,gross_salary',
            'is_taxable' => 'nullable|boolean',
        ]);

        $salaryComponent->update([
            ...$request->only('name', 'code', 'type', 'calculation', 'default_amount', 'percentage_of'),
            'is_taxable' => $request->boolean('is_taxable', true),
        ]);

        return redirect()->route('hr.payroll.components')->with('success', 'Salary component updated.');
    }

    public function destroyComponent(SalaryComponent $salaryComponent)
    {
        $salaryComponent->delete();
        return redirect()->route('hr.payroll.components')->with('success', 'Salary component deleted.');
    }

    // ── Employee Salary Structure ──

    public function employeeSalary(Employee $employee)
    {
        $components = SalaryComponent::active()->orderBy('type')->orderBy('sort_order')->get();
        $overrides = DB::connection('tenant')->table('employee_salary_components')
            ->where('employee_id', $employee->id)
            ->pluck('amount', 'salary_component_id')
            ->toArray();

        return view('admin.hr.payroll.employee-salary', compact('employee', 'components', 'overrides'));
    }

    public function updateEmployeeSalary(Request $request, Employee $employee)
    {
        $request->validate(['overrides' => 'array']);

        DB::connection('tenant')->table('employee_salary_components')
            ->where('employee_id', $employee->id)->delete();

        foreach ($request->input('overrides', []) as $componentId => $amount) {
            if ($amount !== null && $amount !== '') {
                DB::connection('tenant')->table('employee_salary_components')->insert([
                    'employee_id' => $employee->id,
                    'salary_component_id' => $componentId,
                    'amount' => (float) $amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return back()->with('success', 'Salary structure updated.');
    }
}
