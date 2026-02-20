<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'presenting_complaints' => 'nullable|string',
            'history' => 'nullable|string',
            'examination' => 'nullable|string',
            'provisional_diagnosis' => 'nullable|string',
            'diagnosis_dm' => 'nullable|string|max:255',
            'diagnosis_htn' => 'nullable|string|max:255',
            'diagnosis_ihd' => 'nullable|string|max:255',
            'diagnosis_asthma' => 'nullable|string|max:255',
            'allergies' => 'nullable|array',
            'allergies.*' => 'nullable|string|max:255',
            'allergy_notes' => 'nullable|string',
            'gpe_chest' => 'nullable|string|max:255',
            'gpe_abdomen' => 'nullable|string|max:255',
            'gpe_cvs' => 'nullable|string|max:255',
            'gpe_cns' => 'nullable|string|max:255',
            'gpe_pupils' => 'nullable|string|max:255',
            'gpe_conjunctiva' => 'nullable|string|max:255',
            'gpe_nails' => 'nullable|string|max:255',
            'gpe_throat' => 'nullable|string|max:255',
            'gpe_sclera' => 'nullable|string|max:255',
            'gpe_gcs' => 'nullable|string|max:255',
            'treatment' => 'nullable|string',
            'notes' => 'nullable|string',
            'next_visit_date' => 'nullable|date|after_or_equal:today',
        ];
    }
}