<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class LeaveController extends Controller
{
    // ── Leave Requests ──

    public function index(Request $request)
    {
        $query = LeaveRequest::with(['employee', 'leaveType', 'approvedBy']);

        if ($request->status) $query->where('status', $request->status);
        if ($request->employee_id) $query->where('employee_id', $request->employee_id);

        $leaveRequests = $query->latest()->paginate(15)->withQueryString();
        $employees = Employee::active()->orderBy('first_name')->get();

        $stats = [
            'pending' => LeaveRequest::pending()->count(),
            'approved_this_month' => LeaveRequest::approved()
                ->whereMonth('start_date', now()->month)
                ->whereYear('start_date', now()->year)->count(),
            'total_this_month' => LeaveRequest::whereMonth('created_at', now()->month)->count(),
        ];

        return view('admin.hr.leave.index', compact('leaveRequests', 'employees', 'stats'));
    }

    public function create()
    {
        $employees = Employee::active()->orderBy('first_name')->get();
        $leaveTypes = LeaveType::active()->get();
        return view('admin.hr.leave.create', compact('employees', 'leaveTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:tenant.employees,id',
            'leave_type_id' => 'required|exists:tenant.leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_half_day' => 'nullable|boolean',
            'half_day_type' => 'nullable|in:first_half,second_half',
            'reason' => 'required|string|max:1000',
            'document' => 'nullable|file|max:5120',
        ]);

        try {
            $isHalfDay = $request->boolean('is_half_day');
            $totalDays = LeaveRequest::calculateDays(
                Carbon::parse($validated['start_date']),
                Carbon::parse($validated['end_date']),
                $isHalfDay
            );

            // Check balance
            $balance = LeaveBalance::getOrCreate($validated['employee_id'], $validated['leave_type_id']);
            if ($balance->remaining < $totalDays) {
                return back()->withInput()->with('error',
                    "Insufficient leave balance. Available: {$balance->remaining} days, Requested: {$totalDays} days.");
            }

            $docPath = null;
            if ($request->hasFile('document')) {
                $docPath = $request->file('document')->store(tenant_storage_path('leave-docs'), 'public');
            }

            LeaveRequest::create([
                'employee_id' => $validated['employee_id'],
                'leave_type_id' => $validated['leave_type_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'total_days' => $totalDays,
                'is_half_day' => $isHalfDay,
                'half_day_type' => $isHalfDay ? $validated['half_day_type'] : null,
                'reason' => $validated['reason'],
                'document_path' => $docPath,
                'status' => 'pending',
            ]);

            return redirect()->route('hr.leave.index')->with('success', 'Leave request submitted.');
        } catch (\Throwable $e) {
            Log::error('[Leave] Create failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to submit leave request.');
        }
    }

    public function approve(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        try {
            $leaveRequest->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Deduct from balance
            $balance = LeaveBalance::getOrCreate($leaveRequest->employee_id, $leaveRequest->leave_type_id);
            $balance->increment('used_days', $leaveRequest->total_days);

            // Mark attendance as on_leave for the leave dates
            $start = $leaveRequest->start_date;
            $end = $leaveRequest->end_date;
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                Attendance::updateOrCreate(
                    ['employee_id' => $leaveRequest->employee_id, 'date' => $date->format('Y-m-d')],
                    ['status' => 'on_leave', 'notes' => $leaveRequest->leaveType->name, 'marked_by' => auth()->id()]
                );
            }

            return back()->with('success', 'Leave request approved.');
        } catch (\Throwable $e) {
            Log::error('[Leave] Approve failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to approve leave request.');
        }
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be rejected.');
        }

        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return back()->with('success', 'Leave request rejected.');
    }

    public function cancel(LeaveRequest $leaveRequest)
    {
        if (!in_array($leaveRequest->status, ['pending', 'approved'])) {
            return back()->with('error', 'This request cannot be cancelled.');
        }

        // If was approved, restore balance
        if ($leaveRequest->status === 'approved') {
            $balance = LeaveBalance::getOrCreate($leaveRequest->employee_id, $leaveRequest->leave_type_id);
            $balance->decrement('used_days', $leaveRequest->total_days);

            // Remove on_leave attendance entries
            Attendance::where('employee_id', $leaveRequest->employee_id)
                ->where('status', 'on_leave')
                ->whereBetween('date', [$leaveRequest->start_date, $leaveRequest->end_date])
                ->delete();
        }

        $leaveRequest->update(['status' => 'cancelled']);
        return back()->with('success', 'Leave request cancelled.');
    }

    // ── Leave Balances ──

    public function balances(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $employees = Employee::active()->with(['department'])->orderBy('first_name')->get();
        $leaveTypes = LeaveType::active()->get();

        // Ensure balances exist for all employees
        foreach ($employees as $emp) {
            foreach ($leaveTypes as $lt) {
                LeaveBalance::getOrCreate($emp->id, $lt->id, $year);
            }
        }

        $balances = LeaveBalance::where('year', $year)
            ->with(['employee', 'leaveType'])
            ->get()
            ->groupBy('employee_id')
            ->map(fn($items) => $items->keyBy('leave_type_id'));

        return view('admin.hr.leave.balances', compact('employees', 'leaveTypes', 'balances', 'year'));
    }

    // ── Leave Types ──

    public function types()
    {
        $leaveTypes = LeaveType::withCount('requests')->get();
        return view('admin.hr.leave.types', compact('leaveTypes'));
    }

    public function createType()
    {
        return view('admin.hr.leave.create-type');
    }

    public function storeType(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:tenant.leave_types,code',
            'default_days' => 'required|integer|min:0',
            'is_paid' => 'nullable|boolean',
            'is_carry_forward' => 'nullable|boolean',
            'max_carry_forward_days' => 'nullable|integer|min:0',
            'requires_document' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
        ]);

        LeaveType::create([
            ...$request->only('name', 'code', 'default_days', 'max_carry_forward_days', 'description'),
            'is_paid' => $request->boolean('is_paid', true),
            'is_carry_forward' => $request->boolean('is_carry_forward'),
            'requires_document' => $request->boolean('requires_document'),
        ]);

        return redirect()->route('hr.leave-types.index')->with('success', 'Leave type created.');
    }

    public function editType(LeaveType $leaveType)
    {
        return view('admin.hr.leave.edit-type', compact('leaveType'));
    }

    public function updateType(Request $request, LeaveType $leaveType)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:tenant.leave_types,code,' . $leaveType->id,
            'default_days' => 'required|integer|min:0',
            'is_paid' => 'nullable|boolean',
            'is_carry_forward' => 'nullable|boolean',
            'max_carry_forward_days' => 'nullable|integer|min:0',
            'requires_document' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
        ]);

        $leaveType->update([
            ...$request->only('name', 'code', 'default_days', 'max_carry_forward_days', 'description'),
            'is_paid' => $request->boolean('is_paid', true),
            'is_carry_forward' => $request->boolean('is_carry_forward'),
            'requires_document' => $request->boolean('requires_document'),
        ]);

        return redirect()->route('hr.leave-types.index')->with('success', 'Leave type updated.');
    }

    public function destroyType(LeaveType $leaveType)
    {
        if ($leaveType->requests()->count() > 0) {
            return back()->with('error', 'Cannot delete — leave type has existing requests.');
        }
        $leaveType->delete();
        return redirect()->route('hr.leave-types.index')->with('success', 'Leave type deleted.');
    }
}
