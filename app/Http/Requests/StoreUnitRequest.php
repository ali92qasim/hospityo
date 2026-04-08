<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'abbreviation' => 'required|string|max:10|unique:tenant.units',
            'base_unit_id' => 'nullable|exists:tenant.units,id',
            'conversion_factor' => 'required|numeric|min:0.0001',
            'type' => 'required|in:solid,liquid,gas,packaging',
            'is_active' => 'boolean'
        ];
    }
}