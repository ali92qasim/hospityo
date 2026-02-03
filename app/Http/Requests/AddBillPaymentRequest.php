<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddBillPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bill = $this->route('bill');
        
        return [
            'amount' => 'required|numeric|min:0.01|max:' . $bill->due_amount,
            'payment_method' => 'required|in:cash,card,upi,bank_transfer,cheque,insurance',
            'payment_date' => 'required|date'
        ];
    }
}