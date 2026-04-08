<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $unit = $this->route('unit');
        
        return [
            'name' => 'required|string|max:255',
            'abbreviation' => 'required|string|max:10|unique:tenant.units,abbreviation,' . $unit->id,
            'base_unit_id' => 'nullable|exists:tenant.units,id',
            'conversion_factor' => 'required|numeric|min:0.0001',
            'type' => 'required|in:solid,liquid,gas,packaging',
            'is_active' => 'boolean'
        ];
    }
}