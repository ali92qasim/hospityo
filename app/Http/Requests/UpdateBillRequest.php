<?php

namespace App\Http\Requests;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $module = match ($this->input('bill_type')) {
            'pharmacy' => 'pharmacy',
            'emergency' => 'emergency',
            'ipd' => 'ipd',
            default => null,
        };

        if ($module) {
            Tenant::abortUnlessCurrentHasModule($module);
        }

        foreach ($this->input('items', []) as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (! empty($item['lab_test_id'])) {
                Tenant::abortUnlessCurrentHasModule('laboratory');
            }
            if (! empty($item['imaging_study_id'])) {
                Tenant::abortUnlessCurrentHasModule('imaging');
            }
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:tenant.patients,id',
            'bill_date' => 'required|date',
            'bill_type' => 'required|in:opd,ipd,emergency,investigation,pharmacy',
            'items' => 'required|array|min:1',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_percentage' => 'nullable|numeric|min:0|max:100'
        ];
    }
}
