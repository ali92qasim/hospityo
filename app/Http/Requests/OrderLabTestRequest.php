<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderLabTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lab_test_id' => 'required|exists:lab_tests,id',
            'test_location' => 'required|in:indoor,outdoor',
            'priority' => 'required|in:routine,urgent,stat',
            'clinical_notes' => 'nullable|string'
        ];
    }
}