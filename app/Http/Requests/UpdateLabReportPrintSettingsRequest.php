<?php

namespace App\Http\Requests;

use App\Support\LabReportPrintSettings;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLabReportPrintSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $rules = [];
        foreach (array_keys(LabReportPrintSettings::DEFAULTS) as $key) {
            $rules[$key] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $payload = [];
        foreach (array_keys(LabReportPrintSettings::DEFAULTS) as $key) {
            $payload[$key] = $this->boolean($key);
        }

        $this->merge($payload);
    }
}
