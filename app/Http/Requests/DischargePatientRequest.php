<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DischargePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discharge_notes' => 'nullable|string',
            'discharge_summary' => 'required|string',
            'refund_method' => 'nullable|in:cash,card,upi,bank_transfer,cheque',
            'additional_payment_amount' => 'nullable|numeric|min:0',
            'additional_payment_method' => 'nullable|in:cash,card,upi,bank_transfer,cheque,insurance',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $visitParam = $this->route('visit');
            $visit = $visitParam instanceof \App\Models\Visit
                ? $visitParam
                : \App\Models\Visit::with(['admission.advances', 'draftBill'])->find($visitParam);

            if (! $visit || $visit->visit_type !== 'ipd' || ! $visit->admission) {
                return;
            }

            $preview = \App\Services\IpdDischargeBillingService::preview($visit);

            if ($preview['refund_amount'] > 0 && blank($this->input('refund_method'))) {
                $validator->errors()->add(
                    'refund_method',
                    'Select a refund method — advance credit exceeds the final bill by '
                    .number_format($preview['refund_amount'], 2).'.'
                );
            }

            $extra = (float) ($this->input('additional_payment_amount') ?? 0);
            if ($extra > 0 && blank($this->input('additional_payment_method'))) {
                $validator->errors()->add(
                    'additional_payment_method',
                    'Select a payment method for the settlement payment.'
                );
            }
        });
    }
}
