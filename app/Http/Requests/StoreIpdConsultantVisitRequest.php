<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIpdConsultantVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'consultant_doctor_id' => ['nullable', 'exists:tenant.doctors,id'],
            'visit_notes'          => ['nullable', 'string', 'max:5000'],
            'orders'               => ['nullable', 'string', 'max:5000'],
            'consultant_seen_at'   => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (blank($this->input('visit_notes')) && blank($this->input('orders'))) {
                $validator->errors()->add('visit_notes', 'Enter consultant visit notes or orders.');
            }
        });
    }
}
