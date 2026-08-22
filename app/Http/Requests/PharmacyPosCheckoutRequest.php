<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PharmacyPosCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(['prescription', 'walk_in'])],
            'prescription_id' => ['required_if:mode,prescription', 'nullable', 'exists:tenant.prescriptions,id'],
            'patient_id' => ['required', 'exists:tenant.patients,id'],
            'items' => ['required_if:mode,walk_in', 'nullable', 'array', 'min:1'],
            'items.*.medicine_id' => ['required_with:items', 'exists:tenant.medicines,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:9999'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
            'payment_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', Rule::in(['cash', 'card', 'bank_transfer', 'cheque', 'credit'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('payment_method') === 'credit' && (float) $this->input('payment_amount') > 0) {
                $validator->errors()->add('payment_amount', 'Credit sales must not include an upfront payment amount.');
            }
        });
    }
}
