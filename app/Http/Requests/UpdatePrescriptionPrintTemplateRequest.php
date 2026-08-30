<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdatePrescriptionPrintTemplateRequest extends StorePrescriptionPrintTemplateRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = parent::rules();
        $template = $this->route('prescription_print_template');

        $rules['background_image'] = [
            Rule::requiredIf(
                $this->input('mode') === 'digitized_background'
                && blank($template?->background_image_path)
            ),
            'nullable',
            'image',
            'max:10240',
        ];

        return $rules;
    }
}
