<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockOutRequest extends FormRequest
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
            'reason' => 'required|in:expired,damaged,dispensed,adjustment',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string'
        ];
    }
}