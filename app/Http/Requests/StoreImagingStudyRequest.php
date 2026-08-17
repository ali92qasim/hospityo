<?php

namespace App\Http\Requests;

use App\Models\ImagingStudy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreImagingStudyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'code' => 'required|unique:tenant.imaging_studies',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => ['required', Rule::in(ImagingStudy::categories())],
            'price' => 'required|numeric|min:0',
            'turnaround_time' => 'nullable|string|max:100',
            'instructions' => 'nullable|string',
            'parameters' => 'prohibited',
        ];
    }
}
