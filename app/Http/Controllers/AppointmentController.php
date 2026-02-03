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
        return view('admin.appointments.create', compact('patients', 'doctors'));
    }

    public function store(StoreAppointmentRequest $request): JsonResponse|RedirectResponse
    {
        Appointment::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Appointment created successfully.']);
        }

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment created successfully.');
    }

    public function show(Appointment $appointment): View
    {
        $appointment->load(['patient', 'doctor']);
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

        if ($request->expectsJson()) {
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