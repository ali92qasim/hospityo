<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'slug'           => ['nullable', 'string', 'max:63', 'regex:/^[a-z0-9][a-z0-9\-]*[a-z0-9]$/'],
            'email'          => ['required', 'email', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'admin_name'     => ['required', 'string', 'max:255'],
            'admin_email'    => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'confirmed', 'min:8'],
            'plan'           => [
                'nullable',
                'string',
                'exists:plans,slug',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value === null) return;
                    $plan = \App\Models\Plan::where('slug', $value)->first();
                    if ($plan && $plan->isCustomPricing()) {
                        $fail('This plan requires a custom arrangement. Please contact sales to subscribe.');
                    }
                },
            ],
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
