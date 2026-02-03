<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CollectSampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'collection_notes' => 'nullable|string'
        ];
    }
}