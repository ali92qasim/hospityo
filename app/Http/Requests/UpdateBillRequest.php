<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            'bill_date' => 'required|date',
            'bill_type' => 'required|in:opd,ipd,emergency,lab,pharmacy',
            'items' => 'required|array|min:1'
        ];
    }
}