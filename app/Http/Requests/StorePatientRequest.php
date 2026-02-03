<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'age' => 'required|integer|min:1|max:150',
            'phone' => 'required|string|max:20',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'present_address' => 'nullable|string',
            'permanent_address' => 'nullable|string',
            'emergency_name' => 'required|string|max:255',
            'emergency_phone' => 'required|string|max:20',
            'emergency_relation' => 'required|string|max:100',
        ];
    }
}