<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderMultipleImagingStudiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['nullable', 'exists:tenant.doctors,id'],
            'tests' => 'required|array|min:1',
            'tests.*.imaging_study_id' => 'required|exists:tenant.imaging_studies,id',
            'tests.*.quantity' => 'required|integer|min:1|max:10',
            'tests.*.priority' => 'required|in:routine,urgent,stat',
            'tests.*.clinical_notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'tests.required' => 'At least one study must be selected.',
            'tests.*.imaging_study_id.required' => 'Imaging study is required.',
            'tests.*.quantity.required' => 'Quantity is required.',
            'tests.*.priority.required' => 'Priority is required.',
        ];
    }
}
