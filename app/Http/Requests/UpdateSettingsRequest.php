<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'hospital_name' => 'required|string|max:255',
            'hospital_address' => 'required|string',
            'hospital_phone' => 'required|string|max:20',
            'hospital_email' => 'required|email|max:255',
            'hospital_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'currency' => 'required|string|max:10',
            'timezone' => 'required|string',
            'date_format' => 'required|string',
            'time_format' => 'required|string'
        ];
    }
}