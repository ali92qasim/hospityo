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
            'doctor_ids' => ['nullable', 'array'],
            'doctor_ids.*' => ['integer', Rule::exists('doctors', 'id')],
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
