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
        $medicine = $this->route('medicine');
        $medicineId = $medicine instanceof \App\Models\Medicine ? $medicine->id : $medicine;
        
        return [
            'name' => 'required|string|max:255',
            'sku' => [
                'nullable',
                'string',
                'max:255',
                'unique:tenant.medicines,sku,' . $medicineId,
                'regex:/^[A-Za-z0-9\-]+$/'
            ],
            'generic_name' => 'nullable|string|max:255',
            'brand_id' => 'nullable|exists:tenant.medicine_brands,id',
            'category_id' => 'nullable|exists:tenant.medicine_categories,id',
            'dosage_form' => 'nullable|in:tablet,capsule,syrup,suspension,injection,cream,ointment,gel,drops,inhaler,powder,solution,lotion,spray,patch',
            'strength' => 'nullable|string|max:255',
            'base_unit_id' => 'nullable|exists:tenant.units,id',
            'purchase_unit_id' => 'nullable|exists:tenant.units,id',
            'dispensing_unit_id' => 'nullable|exists:tenant.units,id',
            'reorder_level' => 'nullable|integer|min:0',
            'manufacturer' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'manage_stock' => 'nullable|boolean'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $medicine = $this->route('medicine');
            $medicineId = $medicine instanceof \App\Models\Medicine ? $medicine->id : $medicine;
            
            // Check for duplicate medicine based on name, strength, dosage_form, and brand
            $duplicate = \App\Models\Medicine::checkDuplicate(
                $this->name,
                $this->strength,
                $this->dosage_form,
                $this->brand_id,
                $medicineId
            );
            
            if ($duplicate) {
                $validator->errors()->add('name', 
                    'A medicine with the same name' . 
                    ($this->strength ? ', strength' : '') . 
                    ($this->dosage_form ? ', dosage form' : '') . 
                    ($this->brand_id ? ', and brand' : '') . 
                    ' already exists (SKU: ' . $duplicate->sku . '). Please check if this is a duplicate entry.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'This SKU already exists. The system detected a duplicate medicine.',
            'sku.regex' => 'SKU must contain only letters, numbers, and hyphens.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sku' => $this->sku ? strtoupper(trim($this->sku)) : null,
        ]);
    }
}