<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIpdDoctorVisitNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['nullable', 'exists:tenant.doctors,id'],
            'notes'     => ['required', 'string', 'max:5000'],
        ];
    }
}
