<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && $this->user()->id === auth()->id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('tenant.users')->ignore($this->user()->id),
            ],
        ];

        // Add doctor-specific rules when user is a Doctor
        if ($this->user()->hasRole('Doctor')) {
            $rules = array_merge($rules, [
                'phone'            => ['nullable', 'string', 'max:50'],
                'specialization'   => ['nullable', 'string', 'max:255'],
                'qualification'    => ['nullable', 'string', 'max:255'],
                'pmdc_number'      => ['nullable', 'string', 'max:100'],
                'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
                'consultation_fee' => ['nullable', 'numeric', 'min:0'],
                'shift_start'      => ['nullable', 'string', 'max:10'],
                'shift_end'        => ['nullable', 'string', 'max:10'],
                'available_days'   => ['nullable', 'array'],
                'available_days.*' => ['string', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
                'address'          => ['nullable', 'string', 'max:500'],
            ]);
        }

        return $rules;
    }
}
