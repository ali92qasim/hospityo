<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->offsetUnset('visit_type');
    }

    public function rules(): array
    {
        $visit = $this->route('visit');

        return [
            'patient_id' => 'required|exists:tenant.patients,id',
            'doctor_id' => [
                Rule::requiredIf(fn () => $visit && $visit->visit_type !== 'ipd'),
                'nullable',
                'exists:tenant.doctors,id',
            ],
            'visit_datetime' => 'required|date',
            'status' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'closed_at' => 'nullable|date',
            'chief_complaint' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}
