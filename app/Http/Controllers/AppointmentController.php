<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index()
    {
        $appointments = Appointment::with(['patient', 'doctor'])
            ->latest()
            ->paginate(10);
        return view('admin.appointments.index', compact('appointments'));
    }

    public function create()
    {
        $patients = Patient::all();
        $doctors = Doctor::where('status', 'active')->get();
        return view('admin.appointments.create', compact('patients', 'doctors'));
    }

    public function store(StoreAppointmentRequest $request)
    {
        Appointment::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Appointment created successfully.']);
        }

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment created successfully.');
    }

    public function show(Appointment $appointment)
    {
        $appointment->load(['patient', 'doctor']);
        return view('admin.appointments.show', compact('appointment'));
    }

    public function edit(Appointment $appointment)
    {
        $patients = Patient::all();
        $doctors = Doctor::where('status', 'active')->get();
        return view('admin.appointments.edit', compact('appointment', 'patients', 'doctors'));
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $appointment->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Appointment updated successfully.']);
        }

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment updated successfully.');
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment deleted successfully.');
    }

    public function getCalendarEvents(Request $request)
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

    private function getStatusColor($status)
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