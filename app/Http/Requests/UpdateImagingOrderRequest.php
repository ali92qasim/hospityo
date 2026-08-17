<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImagingOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'patient_id'                       => ['required', Rule::exists('tenant.patients', 'id')],
            'doctor_id'                        => ['required', Rule::exists('tenant.doctors', 'id')],
            'visit_id'                         => ['nullable', Rule::exists('tenant.visits', 'id')],
            'clinical_notes'                   => ['nullable', 'string', 'max:2000'],
            'special_instructions'             => ['nullable', 'string', 'max:2000'],

            'items'                            => ['required', 'array', 'min:1'],
            'items.*.imaging_study_id'         => ['required', Rule::exists('tenant.imaging_studies', 'id')],
            'items.*.quantity'                 => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.priority'                 => ['required', Rule::in(['routine', 'urgent', 'stat'])],
            'items.*.clinical_notes'           => ['nullable', 'string', 'max:1000'],
            'items.*.test_location'            => ['required', Rule::in(['indoor', 'outdoor'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $ids = collect($this->input('items', []))
                ->pluck('imaging_study_id')
                ->filter();

            if ($ids->count() !== $ids->unique()->count()) {
                $validator->errors()->add('items', 'Each imaging study can only be added once per order. Please remove duplicate rows.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.required'                    => 'At least one imaging study must be added.',
            'items.min'                         => 'At least one imaging study must be added.',
            'items.*.imaging_study_id.required' => 'Please select an imaging study for each row.',
        ];
    }
}
