<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'code' => 'required|unique:investigations',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:hematology,biochemistry,microbiology,immunology,pathology,molecular',
            'sample_type' => 'required|in:blood,urine,stool,sputum,csf,tissue,swab,other',
            'price' => 'required|numeric|min:0',
            'turnaround_time' => 'required|integer|min:1',
            'instructions' => 'nullable|string',
            'parameters' => 'nullable|array',
            'parameters.*.name' => 'nullable|string',
            'parameters.*.unit' => 'nullable|string',
            'parameters.*.reference_range' => 'nullable|string'
        ];
    }
}