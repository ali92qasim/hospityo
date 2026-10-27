<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', Rule::exists('tenant.patients', 'id')->whereNull('deleted_at')],
            'visit_type' => 'required|in:opd,ipd,emergency',
            'visit_datetime' => 'required|date',
        ];
    }
}