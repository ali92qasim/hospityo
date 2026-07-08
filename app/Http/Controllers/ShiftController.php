<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\DutyRoster;
use App\Models\ShiftSwapRequest;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ShiftController extends Controller
{
    // ── Shift Definitions ──

    public function shifts()
    {
        $shifts = Shift::withCount('dutyRosters')->get();
        return view('admin.hr.shifts.index', compact('shifts'));
    }

    public function createShift()
    {
        return view('admin.hr.shifts.create');
    }

    public function storeShift(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:tenant.shifts,code',
            'start_time' => 'required',
            'end_time' => 'required',
            'break_duration' => 'nullable|numeric|min:0',
            'working_hours' => 'required|numeric|min:1|max:24',
            'grace_minutes' => 'nullable|integer|min:0|max:60',
            'color' => 'nullable|string|max:7',
            'is_overnight' => 'nullable|boolean',
        ]);

        Shift::create([
            ...$request->only('name', 'code', 'start_time', 'end_time', 'break_duration', 'working_hours', 'grace_minutes', 'color'),
            'is_overnight' => $request->boolean('is_overnight'),
        ]);

        return redirect()->route('hr.shifts.index')->with('success', 'Shift created.');
    }

    public function editShift(Shift $shift)
    {
        return view('admin.hr.shifts.edit', compact('shift'));
    }

    public function updateShift(Request $request, Shift $shift)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:tenant.shifts,code,' . $shift->id,
            'start_time' => 'required',
            'end_time' => 'required',
            'break_duration' => 'nullable|numeric|min:0',
            'working_hours' => 'required|numeric|min:1|max:24',
            'grace_minutes' => 'nullable|integer|min:0|max:60',
            'color' => 'nullable|string|max:7',
            'is_overnight' => 'nullable|boolean',
        ]);

        $shift->update([
            ...$request->only('name', 'code', 'start_time', 'end_time', 'break_duration', 'working_hours', 'grace_minutes', 'color'),
            'is_overnight' => $request->boolean('is_overnight'),
        ]);

        return redirect()->route('hr.shifts.index')->with('success', 'Shift updated.');
    }

    public function destroyShift(Shift $shift)
    {
        if ($shift->dutyRosters()->count() > 0) {
            return back()->with('error', 'Cannot delete — shift has roster assignments.');
        }
        $shift->delete();
        return redirect()->route('hr.shifts.index')->with('success', 'Shift deleted.');
    }

    // ── Duty Roster ──

    public function roster(Request $request)
    {
        $period = $request->input('period', 'weekly'); // weekly | monthly | custom

        // Backward compatible: keep `week_start` working for weekly view.
        $weekStart = $request->input('week_start', now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'));

        if ($period === 'monthly') {
            $anchor = Carbon::parse($request->input('month', now()->format('Y-m-01')));
            $startDate = $anchor->copy()->startOfMonth();
            $endDate = $anchor->copy()->endOfMonth();
        } elseif ($period === 'custom') {
            $startDate = Carbon::parse($request->input('start_date', now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d')));
            $endDate = Carbon::parse($request->input('end_date', $startDate->copy()->addDays(6)->format('Y-m-d')));
        } else {
            $startDate = Carbon::parse($weekStart);
            $endDate = $startDate->copy()->addDays(6);
        }

        // Guardrails: ensure sane order and avoid huge tables by accident.
        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        if ($startDate->diffInDays($endDate) > 62) {
            $endDate = $startDate->copy()->addDays(62);
        }

        $weekStart = $startDate->format('Y-m-d');
        $weekEnd = $endDate->format('Y-m-d');
        $departmentId = $request->input('department_id');

        $query = Employee::active()->with(['department', 'designation']);
        if ($departmentId) $query->where('department_id', $departmentId);
        $employees = $query->orderBy('first_name')->get();

        $shifts = Shift::active()->get();
        $departments = Department::orderBy('name')->get();

        $rosters = DutyRoster::with('shift')
            ->forWeek($weekStart, $weekEnd)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy('employee_id')
            ->map(fn($items) => $items->keyBy(fn($r) => $r->date->format('Y-m-d')));

        $dates = [];
        for ($d = Carbon::parse($weekStart); $d->lte(Carbon::parse($weekEnd)); $d->addDay()) {
            $dates[] = $d->copy();
        }

        return view('admin.hr.shifts.roster', compact(
            'employees', 'shifts', 'departments', 'rosters', 'dates', 'weekStart', 'weekEnd', 'period'
        ));
    }

    public function storeRoster(Request $request)
    {
        $request->validate([
            'roster' => 'required|array',
            'roster.*.*.shift_id' => 'nullable|exists:tenant.shifts,id',
        ]);

        $count = 0;

        try {
            foreach ($request->roster as $employeeId => $days) {
                foreach ($days as $date => $data) {
                    $shiftId = $data['shift_id'] ?? null;
                    $isOff = isset($data['is_off']) && $data['is_off'];

                    if (!$shiftId && !$isOff) {
                        DutyRoster::where('employee_id', $employeeId)->where('date', $date)->delete();
                        continue;
                    }

                    DutyRoster::updateOrCreate(
                        ['employee_id' => $employeeId, 'date' => $date],
                        [
                            'shift_id' => $isOff ? ($shiftId ?? Shift::first()?->id) : $shiftId,
                            'is_off_day' => $isOff,
                            'assigned_by' => auth()->id(),
                        ]
                    );
                    $count++;
                }
            }

            return back()->with('success', "Roster saved for {$count} assignments.");
        } catch (\Throwable $e) {
            Log::error('[Shift] Roster save failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to save roster.');
        }
    }

    /**
     * Auto-generate roster for a week based on employee default shifts.
     */
    public function autoGenerate(Request $request)
    {
        $period = $request->input('period', 'weekly');
        $weekStart = $request->input('week_start', now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'));

        if ($period === 'monthly') {
            $anchor = Carbon::parse($request->input('month', now()->format('Y-m-01')));
            $startDate = $anchor->copy()->startOfMonth();
            $endDate = $anchor->copy()->endOfMonth();
        } elseif ($period === 'custom') {
            $startDate = Carbon::parse($request->input('start_date', now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d')));
            $endDate = Carbon::parse($request->input('end_date', $startDate->copy()->addDays(6)->format('Y-m-d')));
        } else {
            $startDate = Carbon::parse($weekStart);
            $endDate = $startDate->copy()->addDays(6);
        }

        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        if ($startDate->diffInDays($endDate) > 62) {
            $endDate = $startDate->copy()->addDays(62);
        }

        $weekStart = $startDate->format('Y-m-d');
        $weekEnd = $endDate->format('Y-m-d');
        $departmentId = $request->input('department_id');

        $query = Employee::active();
        if ($departmentId) $query->where('department_id', $departmentId);
        $employees = $query->get();

        $shifts = Shift::active()->get()->keyBy('code');
        $shiftMap = [
            'morning' => $shifts->get('MOR')?->id ?? $shifts->first()?->id,
            'evening' => $shifts->get('EVE')?->id ?? $shifts->first()?->id,
            'night' => $shifts->get('NGT')?->id ?? $shifts->first()?->id,
            'morning_evening' => $shifts->get('MOR_EVE')?->id ?? $shifts->get('MOR')?->id ?? $shifts->first()?->id,
            'evening_night' => $shifts->get('EVE_NGT')?->id ?? $shifts->get('NGT')?->id ?? $shifts->first()?->id,
            '24_hours' => $shifts->get('H24')?->id ?? $shifts->first()?->id,
        ];

        $count = 0;
        for ($d = Carbon::parse($weekStart); $d->lte(Carbon::parse($weekEnd)); $d->addDay()) {
            $isSunday = $d->isSunday();

            foreach ($employees as $emp) {
                $shiftId = $shiftMap[$emp->default_shift ?? 'morning'] ?? $shifts->first()?->id;
                if (!$shiftId) continue;

                DutyRoster::updateOrCreate(
                    ['employee_id' => $emp->id, 'date' => $d->format('Y-m-d')],
                    [
                        'shift_id' => $shiftId,
                        'is_off_day' => $isSunday,
                        'assigned_by' => auth()->id(),
                    ]
                );
                $count++;
            }
        }

        return back()->with('success', "Auto-generated {$count} roster entries.");
    }

    // ── Shift Swap Requests ──

    public function swapRequests()
    {
        $swapRequests = ShiftSwapRequest::with(['requester', 'target', 'requesterShift', 'targetShift', 'approvedBy'])
            ->latest()->paginate(15);
        return view('admin.hr.shifts.swap-requests', compact('swapRequests'));
    }

    public function approveSwap(ShiftSwapRequest $shiftSwapRequest)
    {
        if ($shiftSwapRequest->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        // Swap the roster entries
        $date = $shiftSwapRequest->swap_date->format('Y-m-d');

        DutyRoster::updateOrCreate(
            ['employee_id' => $shiftSwapRequest->requester_id, 'date' => $date],
            ['shift_id' => $shiftSwapRequest->target_shift_id, 'assigned_by' => auth()->id()]
        );

        DutyRoster::updateOrCreate(
            ['employee_id' => $shiftSwapRequest->target_id, 'date' => $date],
            ['shift_id' => $shiftSwapRequest->requester_shift_id, 'assigned_by' => auth()->id()]
        );

        $shiftSwapRequest->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Shift swap approved and roster updated.');
    }

    public function rejectSwap(ShiftSwapRequest $shiftSwapRequest)
    {
        $shiftSwapRequest->update(['status' => 'rejected', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        return back()->with('success', 'Shift swap rejected.');
    }
}
