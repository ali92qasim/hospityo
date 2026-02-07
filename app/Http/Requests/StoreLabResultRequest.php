<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'results' => 'required|array',
            'interpretation' => 'nullable|string',
            'comments' => 'nullable|string',
            'test_location' => 'nullable|in:indoor,outdoor'
        ];
    }
}