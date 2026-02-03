<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'capacity' => 'required|integer|min:1',
            'ward_type' => 'required|in:general,private,icu,emergency',
            'status' => 'required|in:active,inactive'
        ];
    }
}