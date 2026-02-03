<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DischargePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discharge_notes' => 'nullable|string',
            'discharge_summary' => 'required|string'
        ];
    }
}