<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIpdGpeRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id'       => ['nullable', 'exists:tenant.doctors,id'],
            'gpe_chest'       => ['nullable', 'string', 'max:255'],
            'gpe_abdomen'     => ['nullable', 'string', 'max:255'],
            'gpe_cvs'         => ['nullable', 'string', 'max:255'],
            'gpe_cns'         => ['nullable', 'string', 'max:255'],
            'gpe_pupils'      => ['nullable', 'string', 'max:255'],
            'gpe_conjunctiva' => ['nullable', 'string', 'max:255'],
            'gpe_nails'       => ['nullable', 'string', 'max:255'],
            'gpe_throat'      => ['nullable', 'string', 'max:255'],
            'gpe_sclera'      => ['nullable', 'string', 'max:255'],
            'gpe_gcs'         => ['nullable', 'string', 'max:255'],
            'remarks'         => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $fields = [
                'gpe_chest', 'gpe_abdomen', 'gpe_cvs', 'gpe_cns', 'gpe_pupils',
                'gpe_conjunctiva', 'gpe_nails', 'gpe_throat', 'gpe_sclera', 'gpe_gcs', 'remarks',
            ];

            $hasValue = false;
            foreach ($fields as $field) {
                if (filled($this->input($field))) {
                    $hasValue = true;
                    break;
                }
            }

            if (! $hasValue) {
                $validator->errors()->add('gpe_chest', 'Enter at least one GPE finding or remark before saving.');
            }
        });
    }
}
