<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicineRequest extends FormRequest
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
            'brand_id' => 'nullable|exists:medicine_brands,id',
            'category_id' => 'nullable|exists:medicine_categories,id',
            'dosage_form' => 'nullable|in:tablet,capsule,syrup,suspension,injection,cream,ointment,gel,drops,inhaler,powder,solution,lotion,spray,patch',
            'strength' => 'nullable|string|max:255',
            'base_unit_id' => 'nullable|exists:units,id',
            'purchase_unit_id' => 'nullable|exists:units,id',
            'dispensing_unit_id' => 'nullable|exists:units,id',
            'reorder_level' => 'nullable|integer|min:0',
            'manufacturer' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'manage_stock' => 'nullable|boolean'
        ];
    }
}