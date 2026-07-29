<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIpdDoctorVisitNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes'  => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['pending', 'completed', 'cancelled'])],
        ];
    }
}
