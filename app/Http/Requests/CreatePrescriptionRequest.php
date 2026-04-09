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
            'medicines.*.medicine_id' => 'required|exists:tenant.medicines,id',
            'medicines.*.instruction_id' => 'nullable|exists:tenant.prescription_instructions,id',
            'medicines.*.quantity' => 'nullable|integer|min:1|max:999',
            'notes' => 'nullable|string|max:1000'
        ];
    }

    public function messages(): array
    {
        return [
            'medicines.required' => 'At least one medicine must be selected.',
            'medicines.*.medicine_id.required' => 'Medicine selection is required.',
            'medicines.*.medicine_id.exists' => 'Selected medicine does not exist.',
            'medicines.*.instruction_id.exists' => 'Selected instruction does not exist.'
        ];
    }
}