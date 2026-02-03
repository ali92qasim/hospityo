<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            'visit_type' => 'required|in:opd,ipd,emergency',
            'visit_datetime' => 'required|date',
        ];
    }
}