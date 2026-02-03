<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddTestOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tests' => 'required|array|min:1',
            'tests.*.test_name' => 'required|string',
            'tests.*.quantity' => 'required|integer|min:1',
            'tests.*.priority' => 'required|in:routine,urgent,stat',
            'tests.*.clinical_notes' => 'nullable|string',
        ];
    }
}