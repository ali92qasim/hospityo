<?php

namespace App\Http\Requests;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;

class OrderMultipleLabTestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        Tenant::abortUnlessCurrentHasModule('laboratory');

        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['nullable', 'exists:tenant.doctors,id'],
            'tests' => 'required|array|min:1',
            'tests.*.lab_test_id' => 'required|exists:tenant.lab_tests,id',
            'tests.*.quantity' => 'required|integer|min:1|max:10',
            'tests.*.priority' => 'required|in:routine,urgent,stat',
            'tests.*.clinical_notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'tests.required' => 'At least one test must be selected.',
            'tests.*.lab_test_id.required' => 'Lab test is required.',
            'tests.*.quantity.required' => 'Quantity is required.',
            'tests.*.priority.required' => 'Priority is required.',
        ];
    }
}
