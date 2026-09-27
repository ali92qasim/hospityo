<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLabReportRosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'doctor_ids' => ['required', 'array', 'min:1'],
            'doctor_ids.*' => ['integer', Rule::exists('tenant.doctors', 'id')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'doctor_ids.required' => 'Add at least one consultant to the roster.',
            'doctor_ids.min' => 'Add at least one consultant to the roster.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $ids = collect($this->input('doctor_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->merge(['doctor_ids' => $ids]);
    }
}
