<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $supplier = $this->route('supplier');
        
        return [
            'name' => 'required|string|max:255|unique:suppliers,name,' . $supplier->id,
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'tax_number' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string'
        ];
    }
}