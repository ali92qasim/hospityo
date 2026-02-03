<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVisitRequest extends FormRequest
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
            'visit_type' => 'required|in:opd,ipd,emergency',
            'visit_datetime' => 'required|date',
            'status' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'room_no' => 'nullable|string',
            'bed_no' => 'nullable|string',
            'total_charges' => 'nullable|numeric|min:0',
            'chief_complaint' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment' => 'nullable|string',
            'notes' => 'nullable|string',
            'discharge_datetime' => 'nullable|date',
        ];
    }
}