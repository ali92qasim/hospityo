<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(): View
    {
        $appointments = Appointment::with(['patient', 'doctor'])
            ->latest()
            ->paginate(10);
        return view('admin.appointments.index', compact('appointments'));
    }

    public function create(): View
    {
        $patients = Patient::all();
        $doctors = Doctor::where('status', 'active')->get();
        $doctorSchedules = $doctors->map(fn (Doctor $doctor) => [
            'id' => $doctor->id,
            'available_days' => $doctor->available_days ?? [],
            'shift_start' => $doctor->shift_start ? substr((string) $doctor->shift_start, 0, 5) : null,
            'shift_end' => $doctor->shift_end ? substr((string) $doctor->shift_end, 0, 5) : null,
        ])->values();

        return view('admin.appointments.create', compact('patients', 'doctors', 'doctorSchedules'));
    }

    public function store(StoreAppointmentRequest $request): JsonResponse|RedirectResponse
    {
        Appointment::create($request->validated());

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Appointment created successfully.']);
        }

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment created successfully.');
    }

    public function show(Request $request, Appointment $appointment): View|JsonResponse
    {
        $appointment->load(['patient', 'doctor']);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json($appointment);
        }

        return view('admin.appointments.show', compact('appointment'));
    }

    public function edit(Appointment $appointment): View
    {
        $patients = Patient::all();
        $doctors = Doctor::where('status', 'active')->get();
        return view('admin.appointments.edit', compact('appointment', 'patients', 'doctors'));
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse|RedirectResponse
    {
        $appointment->update($request->validated());

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Appointment updated successfully.']);
        }

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment updated successfully.');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $appointment->delete();

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment deleted successfully.');
    }

    public function getCalendarEvents(Request $request): JsonResponse
    {
        $appointments = Appointment::with(['patient', 'doctor'])
            ->when($request->doctor_id, function($query, $doctorId) {
                return $query->where('doctor_id', $doctorId);
            })
            ->get()
            ->map(function($appointment) {
                return [
                    'id' => $appointment->id,
                    'title' => $appointment->patient->name . ' - Dr. ' . $appointment->doctor->name,
                    'start' => $appointment->appointment_datetime->toISOString(),
                    'backgroundColor' => $this->getStatusColor($appointment->status),
                    'borderColor' => $this->getStatusColor($appointment->status),
                    'extendedProps' => [
                        'patient' => $appointment->patient->name,
                        'doctor' => $appointment->doctor->name,
                        'doctor_id' => $appointment->doctor_id,
                        'status' => $appointment->status,
                        'reason' => $appointment->reason,
                        'appointment' => $appointment,
                    ]
                ];
            });

        return response()->json($appointments);
    }

    private function getStatusColor(string $status): string
    {
        return match($status) {
            'scheduled' => '#3B82F6',
            'completed' => '#10B981',
            'cancelled' => '#EF4444',
            'no_show' => '#6B7280',
            default => '#3B82F6'
        };
    }
}