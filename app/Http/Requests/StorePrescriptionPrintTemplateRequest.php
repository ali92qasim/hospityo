<?php

namespace App\Http\Requests;

use App\Support\PrescriptionPrintFieldCatalog;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StorePrescriptionPrintTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'mode' => ['required', Rule::in(['overlay_physical', 'digitized_background'])],
            'paper_size' => ['required', Rule::in(['A4', 'Letter'])],
            'orientation' => ['required', Rule::in(['portrait', 'landscape'])],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'rx_start_y' => ['required', 'numeric', 'min:0'],
            'rx_row_height' => ['required', 'numeric', 'gt:0'],
            'rx_max_rows' => ['required', 'integer', 'min:1'],
            'rx_overflow_policy' => ['required', Rule::in(['second_page_plain', 'shrink_font', 'cap_with_note'])],
            'background_image' => [
                Rule::requiredIf($this->input('mode') === 'digitized_background'),
                'nullable',
                'image',
                'max:10240',
            ],
            'fields' => ['nullable', 'array:'.implode(',', PrescriptionPrintFieldCatalog::keys())],
            'fields.*' => ['array'],
            'fields.*.x_mm' => ['required', 'numeric', 'min:0'],
            'fields.*.y_mm' => ['required', 'numeric', 'min:0'],
            'fields.*.font_size' => ['required', 'numeric', 'min:1'],
            'fields.*.font_weight' => ['required', Rule::in(['normal', 'bold'])],
            'fields.*.align' => ['required', Rule::in(['left', 'center', 'right'])],
            'fields.*.visible' => ['required', 'boolean'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            parent::failedValidation($validator);
        }

        throw new ValidationException(
            $validator,
            back()->withErrors($validator)->withInput()->setStatusCode(422),
        );
    }
}
