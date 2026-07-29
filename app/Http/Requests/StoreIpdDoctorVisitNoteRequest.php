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
            'notes'     => ['nullable', 'string', 'max:5000'],
            'orders'    => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (blank($this->input('notes')) && blank($this->input('orders'))) {
                $validator->errors()->add('notes', 'Enter visit notes or orders.');
            }
        });
    }
}
