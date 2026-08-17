<?php

namespace App\Http\Requests;

use App\Models\ImagingStudy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImagingStudyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $studyId = $this->route('imagingStudy')?->id
            ?? $this->route('study')
            ?? $this->route('imaging_study');

        return [
            'code' => [
                'required',
                Rule::unique('tenant.imaging_studies', 'code')->ignore($studyId),
            ],
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
