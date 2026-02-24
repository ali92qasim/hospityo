<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionInstructionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'in:frequency,meal,time,duration,conditional,injection'],
            'instruction' => ['required', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'instruction.required' => 'The instruction field is required.',
        ];
    }
}
