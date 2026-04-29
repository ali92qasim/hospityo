<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DepartmentStaffController extends Controller
{
    /**
     * Department-wise staff overview (HR dashboard).
     */
    public function index()
    {
        $departments = Department::withCount([
            'employees as total_employees',
            'employees as active_employees' => fn($q) => $q->where('status', 'active'),
            'employees as on_leave_employees' => fn($q) => $q->where('status', 'on_leave'),
            'doctors as total_doctors' => fn($q) => $q->where('status', 'active'),
        ])->with('headEmployee')->orderBy('name')->get();

        $stats = [
            'total_departments' => $departments->count(),
            'total_staff' => Employee::count(),
            'active_staff' => Employee::active()->count(),
            'total_salary_cost' => Employee::active()->sum('basic_salary'),
        ];

        return view('admin.hr.department-staff.index', compact('departments', 'stats'));
    }

    /**
     * Department detail with staff list, attendance, and cost center data.
     */
    public function show(Department $department)
    {
        $department->load('headEmployee');

        $employees = Employee::where('department_id', $department->id)
            ->with(['designation'])
            ->orderBy('status')
            ->orderBy('first_name')
            ->get();

        // Today's attendance for this department
        $todayAttendance = Attendance::forDate(today()->format('Y-m-d'))
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        // This month's leave requests
        $monthLeaves = LeaveRequest::whereIn('employee_id', $employees->pluck('id'))
            ->where('status', 'approved')
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->with('leaveType')
            ->get();

        // Stats
        $stats = [
            'total' => $employees->count(),
            'active' => $employees->where('status', 'active')->count(),
            'on_leave' => $employees->where('status', 'on_leave')->count(),
            'present_today' => $todayAttendance->whereIn('status', ['present', 'late'])->count(),
            'absent_today' => $todayAttendance->where('status', 'absent')->count(),
            'salary_cost' => $employees->where('status', 'active')->sum('basic_salary'),
            'leaves_this_month' => $monthLeaves->count(),
        ];

        // Employment type breakdown
        $typeBreakdown = $employees->where('status', 'active')->groupBy('employment_type')->map->count();

        // Designation breakdown
        $designationBreakdown = $employees->where('status', 'active')
            ->groupBy(fn($e) => $e->designation?->name ?? 'Unassigned')
            ->map->count()
            ->sortDesc();

        // All departments for head assignment
        $allEmployees = Employee::active()
            ->where('department_id', $department->id)
            ->orderBy('first_name')
            ->get();

        return view('admin.hr.department-staff.show', compact(
            'department', 'employees', 'todayAttendance', 'monthLeaves',
            'stats', 'typeBreakdown', 'designationBreakdown', 'allEmployees'
        ));
    }

    /**
     * Update department head employee.
     */
    public function updateHead(Request $request, Department $department)
    {
        $request->validate([
            'head_employee_id' => 'nullable|exists:tenant.employees,id',
            'monthly_budget' => 'nullable|numeric|min:0',
        ]);

        $department->update($request->only('head_employee_id', 'monthly_budget'));

        // Also update the text field for backward compatibility
        if ($request->head_employee_id) {
            $head = Employee::find($request->head_employee_id);
            $department->update(['head_of_department' => $head?->full_name]);
        }

        return back()->with('success', 'Department settings updated.');
    }

    /**
     * Transfer employee to another department.
     */
    public function transferEmployee(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:tenant.employees,id',
            'department_id' => 'required|exists:tenant.departments,id',
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        $oldDept = $employee->department?->name ?? 'None';
        $employee->update(['department_id' => $request->department_id]);
        $newDept = Department::find($request->department_id)->name;

        return back()->with('success', "{$employee->full_name} transferred from {$oldDept} to {$newDept}.");
    }
}
