<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderMultipleLabTestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'tests' => 'required|array|min:1',
            'tests.*.lab_test_id' => 'required|exists:investigations,id',
            'tests.*.quantity' => 'required|integer|min:1|max:10',
            'tests.*.priority' => 'required|in:routine,urgent,stat',
            'tests.*.clinical_notes' => 'nullable|string|max:500'
        ];
    }

    public function messages(): array
    {
        return [
            'tests.required' => 'At least one test must be selected.',
            'tests.*.lab_test_id.required' => 'Lab test is required.',
            'tests.*.quantity.required' => 'Quantity is required.',
            'tests.*.priority.required' => 'Priority is required.'
        ];
    }
}