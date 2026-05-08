<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLabOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'patient_id'                    => ['required', Rule::exists('tenant.patients', 'id')],
            'doctor_id'                     => ['required', Rule::exists('tenant.doctors', 'id')],
            'visit_id'                      => ['nullable', Rule::exists('tenant.visits', 'id')],
            'clinical_notes'                => ['nullable', 'string', 'max:2000'],
            'special_instructions'          => ['nullable', 'string', 'max:2000'],

            'items'                         => ['required', 'array', 'min:1'],
            'items.*.investigation_id'      => ['required', Rule::exists('tenant.investigations', 'id')],
            'items.*.quantity'              => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.priority'              => ['required', Rule::in(['routine', 'urgent', 'stat'])],
            'items.*.clinical_notes'        => ['nullable', 'string', 'max:1000'],
            'items.*.test_location'         => ['required', Rule::in(['indoor', 'outdoor'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $ids = collect($this->input('items', []))
                ->pluck('investigation_id')
                ->filter();

            if ($ids->count() !== $ids->unique()->count()) {
                $validator->errors()->add('items', 'Each investigation can only be added once per order. Please remove duplicate rows.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.required'                    => 'At least one investigation must be added.',
            'items.min'                         => 'At least one investigation must be added.',
            'items.*.investigation_id.required' => 'Please select an investigation for each row.',
        ];
    }
}
