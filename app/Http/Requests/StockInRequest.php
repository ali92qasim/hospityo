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
            'medicine_id' => 'required|exists:medicines,id',
            'quantity' => 'required|integer|min:1',
            'unit_id' => 'required|exists:units,id',
            'unit_cost' => 'required|numeric|min:0',
            'supplier' => 'required|string|max:255',
            'batch_no' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date|after:today',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string'
        ];
    }
}