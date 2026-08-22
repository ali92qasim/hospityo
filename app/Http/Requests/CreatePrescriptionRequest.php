<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['nullable', 'exists:tenant.doctors,id'],
            'fulfillment_type' => ['required', Rule::in(['in_house', 'external'])],
            'medicines' => 'required|array|min:1',
            'medicines.*.medicine_id' => [
                'required',
                Rule::exists('tenant.medicines', 'id')->whereNotNull('selling_price'),
            ],
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
            'medicines.*.medicine_id.exists' => 'Selected medicine is not available for prescription (missing selling price).',
            'medicines.*.instruction_id.exists' => 'Selected instruction does not exist.'
        ];
    }
}