<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVitalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'blood_pressure' => 'nullable|string',
            'temperature' => 'nullable|numeric',
            'pulse_rate' => 'nullable|integer',
            'spo2' => 'nullable|integer|min:0|max:100',
            'bsr' => 'nullable|numeric|min:0|max:999',
            'weight' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'spo2' => 'SpO₂',
            'bsr' => 'BSR',
        ];
    }
}
