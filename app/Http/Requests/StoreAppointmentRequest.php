<?php

namespace App\Http\Requests;

use App\Models\Doctor;
use App\Services\DoctorAvailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:tenant.patients,id',
            'doctor_id' => 'required|exists:tenant.doctors,id',
            'appointment_datetime' => 'required|date|after_or_equal:today',
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
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $doctor = Doctor::find($this->input('doctor_id'));

            if (! $doctor) {
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
}
