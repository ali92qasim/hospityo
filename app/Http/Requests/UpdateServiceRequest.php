<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $service = $this->route('service');
        
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:services,code,' . $service->id,
            'category' => 'required|in:consultation,procedure,lab_test,imaging,medication,other',
            'price' => 'required|numeric|min:0'
        ];
    }
}