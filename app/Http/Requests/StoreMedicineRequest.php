<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'category' => 'required|string|max:255',
            'dosage_form' => 'required|string|max:255',
            'strength' => 'required|string|max:255',
            'unit_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'expiry_date' => 'required|date|after:today',
            'batch_number' => 'required|string|max:255',
            'manufacturer' => 'required|string|max:255',
            'status' => 'required|in:active,inactive'
        ];
    }
}