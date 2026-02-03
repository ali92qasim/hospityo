<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'medicines' => 'required|array|min:1',
            'medicines.*.medicine_id' => 'required|exists:medicines,id',
            'medicines.*.quantity' => 'required|integer|min:1',
            'medicines.*.dosage' => 'required|string',
            'medicines.*.instructions' => 'nullable|string',
            'notes' => 'nullable|string'
        ];
    }
}