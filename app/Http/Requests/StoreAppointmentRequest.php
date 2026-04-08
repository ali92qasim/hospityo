<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'appointment_datetime' => 'required|date',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}