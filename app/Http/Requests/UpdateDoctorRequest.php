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
            'qualification' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|unique:doctors,email,' . $doctor->id . '|unique:users,email,' . ($doctor->user_id ?? 'NULL'),
            'gender' => 'required|in:male,female,other',
            'experience_years' => 'required|integer|min:0|max:50',
            'address' => 'nullable|string',
            'consultation_fee' => 'required|numeric|min:0',
            'department_id' => 'required|exists:departments,id',
            'available_days' => 'nullable|array',
            'shift_start' => 'required|date_format:H:i',
            'shift_end' => 'required|date_format:H:i|after:shift_start',
            'status' => 'required|in:active,inactive',
        ];
    }
}