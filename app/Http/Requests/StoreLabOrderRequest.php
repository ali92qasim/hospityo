<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'lab_test_id' => 'required|exists:investigations,id',
            'investigation_id' => 'nullable|exists:investigations,id', // Legacy support
            'priority' => 'required|in:routine,urgent,stat',
            'clinical_notes' => 'nullable|string',
            'special_instructions' => 'nullable|string'
        ];
    }

    protected function prepareForValidation()
    {
        // Support both old and new field names
        if ($this->has('investigation_id') && !$this->has('lab_test_id')) {
            $this->merge(['lab_test_id' => $this->investigation_id]);
        }
    }
}