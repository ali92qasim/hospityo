<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public registration
    }

    public function rules(): array
    {
        return [
            'hospital_name'  => ['required', 'string', 'max:255'],
            'slug'           => ['nullable', 'string', 'max:63', 'regex:/^[a-z0-9][a-z0-9\-]*[a-z0-9]$/', 'unique:tenants,slug'],
            'email'          => ['required', 'email', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'admin_name'     => ['required', 'string', 'max:255'],
            'admin_email'    => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'confirmed', Password::defaults()],
            'plan'           => ['nullable', 'string', 'exists:plans,slug'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex'  => 'Subdomain must contain only lowercase letters, numbers, and hyphens.',
            'slug.unique' => 'This subdomain is already taken.',
        ];
    }
}
