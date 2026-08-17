<?php

namespace App\Http\Requests;

use App\Models\LabTest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLabTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $labTestId = $this->route('labTest')?->id
            ?? $this->route('investigation')?->id
            ?? $this->route('lab_test');

        return [
            'code' => [
                'required',
                Rule::unique('tenant.lab_tests', 'code')->ignore($labTestId),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => ['required', Rule::in(LabTest::categories())],
            'sample_type' => 'nullable|in:blood,urine,stool,sputum,csf,tissue,swab,other,n/a',
            'price' => 'required|numeric|min:0',
            'turnaround_time' => 'nullable|string|max:100',
            'instructions' => 'nullable|string',
            'parameters' => 'nullable|array',
            'parameters.*.name' => 'nullable|string',
            'parameters.*.unit' => 'nullable|string',
            'parameters.*.reference_range' => 'nullable|string',
        ];
    }
}
