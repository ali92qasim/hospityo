<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\OperationTheatre;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OTController extends Controller
{
    // ── Conflict Detection API ────────────────────────────────────────────────

    /**
     * OT Calendar view — shows all surgeries on a FullCalendar timeline.
     */
    public function calendar()
    {
        $theatres = OperationTheatre::active()->orderBy('name')->get();
        return view('admin.ot.calendar', compact('theatres'));
    }

    /**
     * JSON feed for the OT calendar. Returns surgeries in FullCalendar event format.
     */
    public function calendarEvents(Request $request)
    {
        try {
            $surgeries = Surgery::with(['patient', 'doctor', 'operationTheatre'])
                ->when($request->theatre_id, function($query, $theatreId) {
                    return $query->where('operation_theatre_id', $theatreId);
                })
                ->get()
                ->map(function($surgery) {
                    // Build start datetime - use toISOString format like appointments
                    $start = $surgery->scheduled_date->format('Y-m-d');
                    if ($surgery->scheduled_start_time) {
                        $start = $surgery->scheduled_date->format('Y-m-d') . 'T' . $surgery->scheduled_start_time;
                    }

                    $color = '#3b82f6';
                    if ($surgery->status === 'in_progress') $color = '#f59e0b';
                    elseif ($surgery->status === 'completed') $color = '#10b981';
                    elseif ($surgery->status === 'cancelled') $color = '#ef4444';
                    elseif ($surgery->status === 'postponed') $color = '#f97316';

                    return [
                        'id' => $surgery->id,
                        'title' => $surgery->procedure_name . ' - ' . ($surgery->patient?->name ?? 'Unknown'),
                        'start' => $start,
                        'backgroundColor' => $color,
                        'borderColor' => $color,
                        'extendedProps' => [
                            'patient' => ($surgery->patient?->name ?? '') . ' - ' . $surgery->procedure_name,
                            'showUrl' => route('ot.surgeries.show', $surgery->id),
                            'status' => $surgery->status,
                            'theatreName' => $surgery->operationTheatre?->name ?? '',
                            'doctorName' => $surgery->doctor?->name ?? '',
                        ],
                    ];
                });

            return response()->json($surgeries);
        } catch (\Throwable $e) {
            Log::error('[OT Calendar] Events failed', ['error' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json([]);
        }
    }

    /**
     * Check for scheduling conflicts on a given theatre/date/time slot.
     * Returns JSON with conflicting surgeries.
     */
    public function checkConflicts(Request $request)
    {
        $request->validate([
            'operation_theatre_id' => 'required|integer',
            'scheduled_date'       => 'required|date',
            'scheduled_start_time' => 'required',
            'scheduled_end_time'   => 'nullable',
            'exclude_surgery_id'   => 'nullable|integer',
        ]);

        $theatreId = $request->operation_theatre_id;
        $date      = $request->scheduled_date;
        $startTime = $request->scheduled_start_time;
        $endTime   = $request->scheduled_end_time ?: $this->estimateEndTime($startTime);
        $excludeId = $request->exclude_surgery_id;

        try {
            $conflicts = Surgery::with('patient')
                ->where('operation_theatre_id', $theatreId)
                ->where('scheduled_date', $date)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->where(function ($q) use ($startTime, $endTime) {
                    // Overlap: existing_start < new_end AND existing_end > new_start
                    $q->where(function ($inner) use ($startTime, $endTime) {
                        $inner->where('scheduled_start_time', '<', $endTime)
                              ->where(function ($sub) use ($startTime) {
                                  $sub->where('scheduled_end_time', '>', $startTime)
                                      ->orWhereNull('scheduled_end_time');
                              });
                    })
                    // Also catch surgeries with no start time on the same date
                    ->orWhereNull('scheduled_start_time');
                })
                ->get();

            $result = $conflicts->map(fn($s) => [
                'id'             => $s->id,
                'procedure_name' => $s->procedure_name,
                'patient_name'   => $s->patient?->name ?? 'Unknown',
                'time_range'     => ($s->scheduled_start_time ?? '?') . ' – ' . ($s->scheduled_end_time ?? '?'),
            ]);

            return response()->json(['conflicts' => $result]);
        } catch (\Throwable $e) {
            Log::error('[OT] Conflict check failed', ['error' => $e->getMessage()]);
            return response()->json(['conflicts' => []], 500);
        }
    }

    /**
     * Estimate end time as start + 2 hours if not provided.
     */
    private function estimateEndTime(string $startTime): string
    {
        try {
            return \Carbon\Carbon::createFromFormat('H:i', $startTime)->addHours(2)->format('H:i');
        } catch (\Throwable $e) {
            return '23:59';
        }
    }

    // ── Operation Theatres ─────────────────────────────────────────────────────

    public function theatres()
    {
        $theatres = OperationTheatre::withCount(['surgeries' => fn($q) => $q->where('status', 'scheduled')])
            ->orderBy('name')
            ->get();

        return view('admin.ot.theatres.index', compact('theatres'));
    }

    public function createTheatre()
    {
        return view('admin.ot.theatres.create');
    }

    public function storeTheatre(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'type'      => 'required|in:general,cardiac,ortho,ent,ophthalmic',
            'floor'     => 'nullable|string|max:50',
            'equipment' => 'nullable|array',
            'notes'     => 'nullable|string|max:1000',
        ]);

        try {
            OperationTheatre::create([
                'name'      => $validated['name'],
                'type'      => $validated['type'],
                'floor'     => $validated['floor'] ?? null,
                'equipment' => $validated['equipment'] ?? null,
                'notes'     => $validated['notes'] ?? null,
                'status'    => 'available',
                'is_active' => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('[OT] Failed to create theatre', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to create operation theatre.');
        }

        return redirect()->route('ot.theatres')->with('success', 'Operation theatre created.');
    }

    public function editTheatre(OperationTheatre $theatre)
    {
        return view('admin.ot.theatres.edit', compact('theatre'));
    }

    public function updateTheatre(Request $request, OperationTheatre $theatre)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'type'      => 'required|in:general,cardiac,ortho,ent,ophthalmic',
            'status'    => 'required|in:available,occupied,maintenance',
            'floor'     => 'nullable|string|max:50',
            'equipment' => 'nullable|array',
            'is_active' => 'boolean',
            'notes'     => 'nullable|string|max:1000',
        ]);

        try {
            $theatre->update($validated);
        } catch (\Throwable $e) {
            Log::error('[OT] Failed to update theatre', ['id' => $theatre->id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to update operation theatre.');
        }

        return redirect()->route('ot.theatres')->with('success', 'Operation theatre updated.');
    }

    // ── Surgeries ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $patients = Patient::orderBy('name')->get();
        $doctors  = Doctor::where('status', 'active')->orderBy('name')->get();
        $theatres = OperationTheatre::active()->orderBy('name')->get();

        return view('admin.ot.surgeries.index', compact('patients', 'doctors', 'theatres'));
    }

    public function create(Request $request)
    {
        $patients = Patient::orderBy('name')->get();
        $doctors  = Doctor::where('status', 'active')->orderBy('name')->get();
        $theatres = OperationTheatre::available()->orderBy('name')->get();
        $users    = User::orderBy('name')->get();
        $prefilledDate = $request->query('date', old('scheduled_date', date('Y-m-d')));

        return view('admin.ot.surgeries.create', compact('patients', 'doctors', 'theatres', 'users', 'prefilledDate'));
    }

    public function store(Request $request)
    {
        // Filter out empty team entries (rows where user didn't select anyone)
        if ($request->has('team')) {
            $team = collect($request->team)->filter(fn($m) => !empty($m['user_id']))->values()->all();
            $request->merge(['team' => !empty($team) ? $team : null]);
        }

        // Support both combined datetime (from modal) and separate date/time fields (from full form)
        if ($request->filled('scheduled_datetime') && !$request->filled('scheduled_date')) {
            $dt = \Carbon\Carbon::parse($request->scheduled_datetime);
            $request->merge([
                'scheduled_date'       => $dt->format('Y-m-d'),
                'scheduled_start_time' => $dt->format('H:i'),
            ]);
        }

        $validated = $request->validate([
            'patient_id'            => 'required|exists:tenant.patients,id',
            'doctor_id'             => 'required|exists:tenant.doctors,id',
            'operation_theatre_id'  => 'nullable|exists:tenant.operation_theatres,id',
            'surgery_type'          => 'required|in:elective,emergency',
            'procedure_name'        => 'required|string|max:255',
            'procedure_code'        => 'nullable|string|max:50',
            'scheduled_date'        => 'required|date|after_or_equal:today',
            'scheduled_start_time'  => 'nullable',
            'scheduled_end_time'    => 'nullable',
            'pre_op_diagnosis'      => 'nullable|string|max:2000',
            'anesthesia_type'       => 'nullable|in:general,local,spinal,epidural,sedation',
            'team'                  => 'nullable|array',
            'team.*.user_id'        => 'required_with:team|exists:tenant.users,id',
            'team.*.role'           => 'required_with:team|in:lead_surgeon,assistant_surgeon,anesthetist,nurse,technician',
        ]);

        // Server-side conflict check — block if exact overlap on same theatre
        if (!empty($validated['operation_theatre_id']) && !empty($validated['scheduled_start_time'])) {
            $endTime = $validated['scheduled_end_time'] ?? $this->estimateEndTime($validated['scheduled_start_time']);
            $conflict = Surgery::where('operation_theatre_id', $validated['operation_theatre_id'])
                ->where('scheduled_date', $validated['scheduled_date'])
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->where('scheduled_start_time', '<', $endTime)
                ->where(function ($q) use ($validated) {
                    $q->where('scheduled_end_time', '>', $validated['scheduled_start_time'])
                      ->orWhereNull('scheduled_end_time');
                })
                ->exists();

            if ($conflict && $validated['surgery_type'] !== 'emergency') {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['message' => 'This theatre is already booked during the selected time slot.'], 422);
                }
                return back()->withInput()->with('error', 'This theatre is already booked during the selected time slot. Use "Emergency" type to override.');
            }
        }

        try {
            DB::connection('tenant')->transaction(function () use ($validated, $request) {
                $surgery = Surgery::create([
                    'patient_id'            => $validated['patient_id'],
                    'doctor_id'             => $validated['doctor_id'],
                    'operation_theatre_id'  => $validated['operation_theatre_id'] ?? null,
                    'surgery_type'          => $validated['surgery_type'],
                    'procedure_name'        => $validated['procedure_name'],
                    'procedure_code'        => $validated['procedure_code'] ?? null,
                    'scheduled_date'        => $validated['scheduled_date'],
                    'scheduled_start_time'  => $validated['scheduled_start_time'] ?? null,
                    'scheduled_end_time'    => $validated['scheduled_end_time'] ?? null,
                    'pre_op_diagnosis'      => $validated['pre_op_diagnosis'] ?? null,
                    'anesthesia_type'       => $validated['anesthesia_type'] ?? null,
                    'status'                => 'scheduled',
                    'created_by'            => auth()->id(),
                ]);

                // Add team members
                if (!empty($validated['team'])) {
                    foreach ($validated['team'] as $member) {
                        $surgery->teamMembers()->create([
                            'user_id' => $member['user_id'],
                            'role'    => $member['role'],
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::error('[OT] Failed to schedule surgery', ['error' => $e->getMessage()]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => 'Failed to schedule surgery.'], 500);
            }
            return back()->withInput()->with('error', 'Failed to schedule surgery. Please try again.');
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Surgery scheduled successfully.']);
        }
        return redirect()->route('ot.surgeries.index')->with('success', 'Surgery scheduled successfully.');
    }

    public function show(Surgery $surgery)
    {
        $surgery->load(['patient', 'doctor', 'operationTheatre', 'teamMembers.user', 'createdBy', 'pacCheckup', 'surgicalChecklist', 'consumableUsages.consumable', 'anaesthesiaRecord', 'operativeVitals', 'postOpMonitoring']);
        return view('admin.ot.surgeries.show', compact('surgery'));
    }

    public function edit(Surgery $surgery)
    {
        if (!in_array($surgery->status, ['scheduled', 'postponed'])) {
            return redirect()->route('ot.surgeries.show', $surgery)
                ->with('error', 'Only scheduled or postponed surgeries can be edited.');
        }

        $surgery->load('teamMembers');
        $patients = Patient::orderBy('name')->get();
        $doctors  = Doctor::where('status', 'active')->orderBy('name')->get();
        $theatres = OperationTheatre::active()->orderBy('name')->get();
        $users    = User::orderBy('name')->get();

        return view('admin.ot.surgeries.edit', compact('surgery', 'patients', 'doctors', 'theatres', 'users'));
    }

    public function update(Request $request, Surgery $surgery)
    {
        if (!in_array($surgery->status, ['scheduled', 'postponed'])) {
            return back()->with('error', 'Only scheduled or postponed surgeries can be edited.');
        }

        // Filter out empty team entries
        if ($request->has('team')) {
            $team = collect($request->team)->filter(fn($m) => !empty($m['user_id']))->values()->all();
            $request->merge(['team' => !empty($team) ? $team : null]);
        }

        $validated = $request->validate([
            'patient_id'            => 'required|exists:tenant.patients,id',
            'doctor_id'             => 'required|exists:tenant.doctors,id',
            'operation_theatre_id'  => 'nullable|exists:tenant.operation_theatres,id',
            'surgery_type'          => 'required|in:elective,emergency',
            'procedure_name'        => 'required|string|max:255',
            'procedure_code'        => 'nullable|string|max:50',
            'scheduled_date'        => 'required|date',
            'scheduled_start_time'  => 'nullable',
            'scheduled_end_time'    => 'nullable',
            'pre_op_diagnosis'      => 'nullable|string|max:2000',
            'anesthesia_type'       => 'nullable|in:general,local,spinal,epidural,sedation',
            'team'                  => 'nullable|array',
            'team.*.user_id'        => 'required_with:team|exists:tenant.users,id',
            'team.*.role'           => 'required_with:team|in:lead_surgeon,assistant_surgeon,anesthetist,nurse,technician',
        ]);

        // Server-side conflict check (exclude current surgery)
        if (!empty($validated['operation_theatre_id']) && !empty($validated['scheduled_start_time'])) {
            $endTime = $validated['scheduled_end_time'] ?? $this->estimateEndTime($validated['scheduled_start_time']);
            $conflict = Surgery::where('operation_theatre_id', $validated['operation_theatre_id'])
                ->where('scheduled_date', $validated['scheduled_date'])
                ->where('id', '!=', $surgery->id)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->where('scheduled_start_time', '<', $endTime)
                ->where(function ($q) use ($validated) {
                    $q->where('scheduled_end_time', '>', $validated['scheduled_start_time'])
                      ->orWhereNull('scheduled_end_time');
                })
                ->exists();

            if ($conflict && $validated['surgery_type'] !== 'emergency') {
                return back()->withInput()->with('error', 'This theatre is already booked during the selected time slot. Use "Emergency" type to override.');
            }
        }

        try {
            DB::connection('tenant')->transaction(function () use ($validated, $surgery) {
                $surgery->update([
                    'patient_id'            => $validated['patient_id'],
                    'doctor_id'             => $validated['doctor_id'],
                    'operation_theatre_id'  => $validated['operation_theatre_id'] ?? null,
                    'surgery_type'          => $validated['surgery_type'],
                    'procedure_name'        => $validated['procedure_name'],
                    'procedure_code'        => $validated['procedure_code'] ?? null,
                    'scheduled_date'        => $validated['scheduled_date'],
                    'scheduled_start_time'  => $validated['scheduled_start_time'] ?? null,
                    'scheduled_end_time'    => $validated['scheduled_end_time'] ?? null,
                    'pre_op_diagnosis'      => $validated['pre_op_diagnosis'] ?? null,
                    'anesthesia_type'       => $validated['anesthesia_type'] ?? null,
                ]);

                // Replace team
                $surgery->teamMembers()->delete();
                if (!empty($validated['team'])) {
                    foreach ($validated['team'] as $member) {
                        $surgery->teamMembers()->create([
                            'user_id' => $member['user_id'],
                            'role'    => $member['role'],
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::error('[OT] Failed to update surgery', ['id' => $surgery->id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to update surgery.');
        }

        return redirect()->route('ot.surgeries.show', $surgery)->with('success', 'Surgery updated.');
    }

    // ── Status Transitions ────────────────────────────────────────────────────

    public function start(Surgery $surgery)
    {
        if ($surgery->status !== 'scheduled') {
            return back()->with('error', 'Only scheduled surgeries can be started.');
        }

        // PAC clearance gate — emergency surgeries bypass
        if ($surgery->surgery_type !== 'emergency') {
            $pac = $surgery->pacCheckup;
            if (!$pac) {
                return back()->with('error', 'Cannot start surgery — Pre-Anaesthesia Checkup (PAC) has not been requested. Please submit a PAC request first.');
            }
            if (!$pac->isCleared()) {
                return back()->with('error', 'Cannot start surgery — PAC clearance is pending or was not approved. Current PAC status: ' . ucfirst(str_replace('_', ' ', $pac->status)));
            }

            // Surgical Safety Checklist — Sign In must be complete
            $checklist = $surgery->surgicalChecklist;
            if ($checklist && !$checklist->isSignInComplete()) {
                return back()->with('error', 'Cannot start surgery — WHO Surgical Safety Checklist "Sign In" phase is not complete.');
            }
        }

        try {
            $surgery->update([
                'status'            => 'in_progress',
                'actual_start_time' => now(),
            ]);

            // Mark OT as occupied
            if ($surgery->operation_theatre_id) {
                $surgery->operationTheatre->update(['status' => 'occupied']);
            }
        } catch (\Throwable $e) {
            Log::error('[OT] Failed to start surgery', ['id' => $surgery->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to start surgery.');
        }

        return back()->with('success', 'Surgery started.');
    }

    public function complete(Request $request, Surgery $surgery)
    {
        if ($surgery->status !== 'in_progress') {
            return back()->with('error', 'Only in-progress surgeries can be completed.');
        }

        $request->validate([
            'post_op_diagnosis' => 'nullable|string|max:2000',
            'procedure_notes'   => 'nullable|string|max:5000',
            'complications'     => 'nullable|string|max:2000',
        ]);

        try {
            $surgery->update([
                'status'            => 'completed',
                'actual_end_time'   => now(),
                'post_op_diagnosis' => $request->post_op_diagnosis,
                'procedure_notes'   => $request->procedure_notes,
                'complications'     => $request->complications,
            ]);

            // Free OT
            if ($surgery->operation_theatre_id) {
                $surgery->operationTheatre->update(['status' => 'available']);
            }
        } catch (\Throwable $e) {
            Log::error('[OT] Failed to complete surgery', ['id' => $surgery->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to complete surgery.');
        }

        return back()->with('success', 'Surgery completed successfully.');
    }

    public function cancel(Request $request, Surgery $surgery)
    {
        if (in_array($surgery->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'This surgery cannot be cancelled.');
        }

        $request->validate([
            'cancelled_reason' => 'required|string|max:500',
        ]);

        try {
            $surgery->update([
                'status'           => 'cancelled',
                'cancelled_reason' => $request->cancelled_reason,
            ]);

            // Free OT if it was occupied
            if ($surgery->operation_theatre_id && $surgery->operationTheatre?->status === 'occupied') {
                $surgery->operationTheatre->update(['status' => 'available']);
            }
        } catch (\Throwable $e) {
            Log::error('[OT] Failed to cancel surgery', ['id' => $surgery->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to cancel surgery.');
        }

        return back()->with('success', 'Surgery cancelled.');
    }

    public function postpone(Request $request, Surgery $surgery)
    {
        if (!in_array($surgery->status, ['scheduled'])) {
            return back()->with('error', 'Only scheduled surgeries can be postponed.');
        }

        $request->validate([
            'postponed_reason' => 'required|string|max:500',
            'new_date'         => 'nullable|date|after:today',
        ]);

        try {
            $data = [
                'status'           => 'postponed',
                'postponed_reason' => $request->postponed_reason,
            ];

            if ($request->filled('new_date')) {
                $data['scheduled_date'] = $request->new_date;
            }

            $surgery->update($data);

            // Free OT
            if ($surgery->operation_theatre_id && $surgery->operationTheatre?->status === 'occupied') {
                $surgery->operationTheatre->update(['status' => 'available']);
            }
        } catch (\Throwable $e) {
            Log::error('[OT] Failed to postpone surgery', ['id' => $surgery->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to postpone surgery.');
        }

        return back()->with('success', 'Surgery postponed.');
    }
}
