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
            'medicines.*.dosage' => 'required|string|max:255',
            'medicines.*.instructions' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000'
        ];
    }

    public function messages(): array
    {
        return [
            'medicines.required' => 'At least one medicine must be selected.',
            'medicines.*.medicine_id.required' => 'Medicine selection is required.',
            'medicines.*.medicine_id.exists' => 'Selected medicine does not exist.',
            'medicines.*.quantity.required' => 'Quantity is required for each medicine.',
            'medicines.*.quantity.min' => 'Quantity must be at least 1.',
            'medicines.*.dosage.required' => 'Dosage is required for each medicine.'
        ];
    }
}