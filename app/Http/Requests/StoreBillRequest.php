<?php

namespace App\Http\Requests;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;

class StoreBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $this->assertBillTypeEntitled();
        $this->assertLineItemsEntitled();

        return true;
    }

    private function assertBillTypeEntitled(): void
    {
        $module = match ($this->input('bill_type')) {
            'pharmacy' => 'pharmacy',
            'emergency' => 'emergency',
            'ipd' => 'ipd',
            default => null,
        };

        if ($module) {
            Tenant::abortUnlessCurrentHasModule($module);
        }
    }

    private function assertLineItemsEntitled(): void
    {
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
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:tenant.patients,id',
            'bill_date' => 'required|date',
            'bill_type' => 'required|in:opd,ipd,emergency,pharmacy',
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'nullable|exists:tenant.services,id',
            'items.*.lab_test_id' => 'nullable|exists:tenant.lab_tests,id',
            'items.*.imaging_study_id' => 'nullable|exists:tenant.imaging_studies,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
