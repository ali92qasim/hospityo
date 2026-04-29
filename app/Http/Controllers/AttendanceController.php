<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', today()->format('Y-m-d'));
        $departmentId = $request->input('department_id');

        $query = Attendance::with(['employee.department', 'employee.designation'])
            ->forDate($date);

        if ($departmentId) {
            $query->whereHas('employee', fn($q) => $q->where('department_id', $departmentId));
        }

        $attendances = $query->orderBy('check_in')->get();
        $departments = Department::orderBy('name')->get();

        // Stats for the day
        $stats = [
            'total' => Employee::active()->count(),
            'present' => $attendances->whereIn('status', ['present', 'late'])->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'on_leave' => $attendances->where('status', 'on_leave')->count(),
            'not_marked' => Employee::active()->count() - $attendances->count(),
        ];

        return view('admin.hr.attendance.index', compact('attendances', 'departments', 'stats', 'date'));
    }

    public function markDaily(Request $request)
    {
        $date = $request->input('date', today()->format('Y-m-d'));
        $departmentId = $request->input('department_id');

        $query = Employee::active()->with(['department', 'designation']);
        if ($departmentId) $query->where('department_id', $departmentId);

        $employees = $query->orderBy('first_name')->get();
        $departments = Department::orderBy('name')->get();

        // Get existing attendance for this date
        $existing = Attendance::forDate($date)->pluck('status', 'employee_id')->toArray();
        $existingTimes = Attendance::forDate($date)->get()->keyBy('employee_id');

        return view('admin.hr.attendance.mark', compact('employees', 'departments', 'date', 'existing', 'existingTimes'));
    }

    public function storeDaily(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.status' => 'required|in:present,absent,late,half_day,on_leave,holiday',
        ]);

        $date = $request->input('date');
        $count = 0;

        try {
            foreach ($request->attendance as $employeeId => $data) {
                $attendance = Attendance::updateOrCreate(
                    ['employee_id' => $employeeId, 'date' => $date],
                    [
                        'status' => $data['status'],
                        'check_in' => $data['check_in'] ?? null,
                        'check_out' => $data['check_out'] ?? null,
                        'shift' => $data['shift'] ?? null,
                        'notes' => $data['notes'] ?? null,
                        'marked_by' => auth()->id(),
                    ]
                );

                $attendance->calculateWorkedHours();
                $attendance->save();
                $count++;
            }

            return redirect()->route('hr.attendance.index', ['date' => $date])
                ->with('success', "Attendance marked for {$count} employees.");
        } catch (\Throwable $e) {
            Log::error('[Attendance] Mark failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to save attendance.');
        }
    }

    public function monthly(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));
        $departmentId = $request->input('department_id');

        $query = Employee::active()->with(['department']);
        if ($departmentId) $query->where('department_id', $departmentId);
        $employees = $query->orderBy('first_name')->get();

        $attendances = Attendance::forMonth($year, $month)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy('employee_id')
            ->map(fn($items) => $items->keyBy(fn($a) => $a->date->day)->map(fn($a) => $a->status));

        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        $departments = Department::orderBy('name')->get();

        return view('admin.hr.attendance.monthly', compact(
            'employees', 'attendances', 'year', 'month', 'daysInMonth', 'departments'
        ));
    }
}
