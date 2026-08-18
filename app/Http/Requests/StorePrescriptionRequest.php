<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'visit_id' => 'required|exists:tenant.visits,id',
            'notes' => 'nullable|string',
            'medicines' => 'required|array|min:1',
            'medicines.*.medicine_id' => [
                'required',
                Rule::exists('tenant.medicines', 'id')->whereNotNull('selling_price'),
            ],
            'medicines.*.quantity' => 'required|integer|min:1',
            'medicines.*.dosage' => 'required|string',
            'medicines.*.frequency' => 'required|string',
            'medicines.*.duration' => 'required|string',
            'medicines.*.instructions' => 'nullable|string'
        ];
    }

    public function messages(): array
    {
        return [
            'medicines.*.medicine_id.exists' => 'Selected medicine is not available for prescription (missing selling price).',
        ];
    }
}