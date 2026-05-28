<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'medicine_id'  => 'required|exists:tenant.medicines,id',
            'quantity'     => 'required|integer|min:1',
            'unit_id'      => 'required|exists:tenant.units,id',
            'unit_cost'    => 'required|numeric|min:0',
            'supplier'     => 'required|string|max:255',
            'batch_no'     => 'required|string|max:100',
            'expiry_date'  => 'required|date|after:today',
            'reference_no' => 'nullable|string|max:100',
            'notes'        => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'batch_no.required'    => 'Batch number is required for every stock entry.',
            'expiry_date.required' => 'Expiry date is required for every stock entry.',
            'expiry_date.after'    => 'Expiry date must be a future date.',
        ];
    }
}