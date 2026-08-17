<?php

namespace App\Http\Requests;

use App\Models\LabTest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLabTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'code' => 'required|unique:tenant.lab_tests',
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
