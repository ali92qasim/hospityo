<?php

namespace App\Http\Requests;

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
        $investigationId = $this->route('investigation');

        return [
            'code' => [
                'required',
                Rule::unique('tenant.investigations', 'code')->ignore($investigationId),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:hematology,biochemistry,microbiology,immunology,histopathology,molecular,x-ray,ultrasound,ct-scan,mri,cardiac-diagnostics',
            'sample_type' => 'nullable|in:blood,urine,stool,sputum,csf,tissue,swab,other',
            'price' => 'required|numeric|min:0',
            'turnaround_time' => 'nullable|string|max:100',
            'instructions' => 'nullable|string',
        ];
    }
}
