<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $doctor = $this->route('doctor');
        
        return [
            'name' => 'required|string|max:255',
            'specialization' => 'required|string|max:255',
            'qualification' => 'required|string|max:500',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255|unique:doctors,email,' . $doctor->id . '|unique:users,email,' . ($doctor->user_id ?? 'NULL'),
            'gender' => 'required|in:male,female,other',
            'experience_years' => 'required|integer|min:0|max:50',
            'address' => 'nullable|string|max:1000',
            'consultation_fee' => 'required|numeric|min:0|max:999999.99',
            'department_id' => 'required|exists:departments,id',
            'available_days' => 'nullable|array|max:7',
            'shift_start' => 'required|date_format:H:i',
            'shift_end' => 'required|date_format:H:i|after:shift_start',
            'status' => 'required|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already in use by another doctor or user.',
            'shift_end.after' => 'Shift end time must be after shift start time.',
            'experience_years.max' => 'Experience years cannot exceed 50.',
            'consultation_fee.max' => 'Consultation fee cannot exceed 999,999.99.',
            'available_days.max' => 'Cannot select more than 7 days.'
        ];
    }
}