<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:services,code',
            'category' => 'required|in:consultation,procedure,lab_test,imaging,medication,other',
            'price' => 'required|numeric|min:0'
        ];
    }
}