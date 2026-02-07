<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'bed_number' => 'required|string|max:255',
            'ward_id' => 'required|exists:wards,id',
            'bed_type' => 'required|in:general,private,icu,emergency',
            'daily_rate' => 'required|numeric|min:0',
            'status' => 'required|in:available,occupied,maintenance'
        ];
    }
}
