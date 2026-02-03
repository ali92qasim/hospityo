<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TriagePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'priority_level' => 'required|in:critical,urgent,less_urgent,non_urgent',
            'chief_complaint' => 'required|string',
            'pain_scale' => 'nullable|integer|min:0|max:10',
            'triage_notes' => 'nullable|string'
        ];
    }
}