<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\DoctorAvailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        if ($this->isPastAppointment()) {
            return [
                'status' => 'sometimes|required|in:scheduled,completed,cancelled,no_show',
                'notes' => 'nullable|string',
            ];
        }

        return [
            'patient_id' => 'sometimes|required|exists:tenant.patients,id',
            'doctor_id' => 'sometimes|required|exists:tenant.doctors,id',
            'appointment_datetime' => 'required|date|after_or_equal:today',
            'status' => 'sometimes|required|in:scheduled,completed,cancelled,no_show',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_datetime.after_or_equal' => 'Appointments cannot be booked on a past date.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $appointment = $this->appointment();

            if ($this->isPastAppointment()) {
                $this->rejectPastAppointmentMutations($validator, $appointment);

                return;
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $doctorId = $this->input('doctor_id', $appointment?->doctor_id);
            $doctor = Doctor::find($doctorId);

            if (! $doctor || ! $this->filled('appointment_datetime')) {
                return;
            }

            $reason = app(DoctorAvailability::class)->failureReason(
                $doctor,
                Carbon::parse($this->input('appointment_datetime'))
            );

            if ($reason) {
                $validator->errors()->add('appointment_datetime', $reason);
            }
        });
    }

    private function appointment(): ?Appointment
    {
        $appointment = $this->route('appointment');

        return $appointment instanceof Appointment ? $appointment : null;
    }

    private function isPastAppointment(): bool
    {
        $appointment = $this->appointment();

        if ($appointment?->appointment_datetime === null) {
            return false;
        }

        return $appointment->appointment_datetime->toDateString() < now()->toDateString();
    }

    private function rejectPastAppointmentMutations($validator, ?Appointment $appointment): void
    {
        if (! $appointment) {
            return;
        }

        if ($this->filled('appointment_datetime')) {
            $incoming = Carbon::parse($this->input('appointment_datetime'))->format('Y-m-d H:i');
            $original = $appointment->appointment_datetime->format('Y-m-d H:i');

            if ($incoming !== $original) {
                $validator->errors()->add(
                    'appointment_datetime',
                    'Past appointments can only update status and notes.'
                );
            }
        }

        if ($this->filled('patient_id') && (int) $this->input('patient_id') !== (int) $appointment->patient_id) {
            $validator->errors()->add('patient_id', 'Past appointments can only update status and notes.');
        }

        if ($this->filled('doctor_id') && (int) $this->input('doctor_id') !== (int) $appointment->doctor_id) {
            $validator->errors()->add('doctor_id', 'Past appointments can only update status and notes.');
        }
    }
}
