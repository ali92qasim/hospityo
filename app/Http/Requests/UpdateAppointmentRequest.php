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
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_datetime' => 'required|date',
            'status' => 'required|in:scheduled,completed,cancelled,no_show',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}