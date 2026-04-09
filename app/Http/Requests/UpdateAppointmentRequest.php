<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'sometimes|required|exists:tenant.patients,id',
            'doctor_id' => 'sometimes|required|exists:tenant.doctors,id',
            'appointment_datetime' => 'required|date',
            'status' => 'sometimes|required|in:scheduled,completed,cancelled,no_show',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}