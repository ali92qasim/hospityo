<?php

namespace App\Http\Requests;

use App\Models\InventoryTransaction;
use App\Models\Unit;
use App\Services\MedicineStockConversion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('stock_in_mode')) {
            $this->merge(['stock_in_mode' => 'new']);
        }
    }

    public function rules(): array
    {
        $mode = $this->input('stock_in_mode', 'new');

        $rules = [
            'medicine_id'   => 'required|exists:tenant.medicines,id',
            'quantity'      => 'required|integer|min:1',
            'unit_id'       => 'required|exists:tenant.units,id',
            'supplier'      => 'required|string|max:255',
            'reference_no'  => 'nullable|string|max:100',
            'notes'         => 'nullable|string|max:1000',
            'stock_in_mode' => 'required|in:new,existing',
        ];

        if ($mode === 'existing') {
            $rules['existing_batch_id'] = 'required|integer|exists:tenant.inventory_transactions,id';
        } else {
            $rules['unit_cost']   = 'required|numeric|min:0';
            $rules['batch_no']    = 'required|string|max:100';
            $rules['expiry_date'] = 'required|date|after:today';
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $mode = $this->input('stock_in_mode', 'new');

            if ($mode === 'existing') {
                $batch = InventoryTransaction::find($this->input('existing_batch_id'));

                if (! $batch) {
                    return;
                }

                if ((int) $batch->medicine_id !== (int) $this->input('medicine_id')) {
                    $validator->errors()->add('existing_batch_id', 'The selected batch does not belong to this medicine.');
                }

                if ($batch->type !== 'stock_in') {
                    $validator->errors()->add('existing_batch_id', 'The selected batch is not a stock-in transaction.');
                }

                return;
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $unit = Unit::find($this->input('unit_id'));
            $batchNo = $this->input('batch_no');

            if (! $unit || ! $batchNo) {
                return;
            }

            $converted = MedicineStockConversion::toBaseUnits(
                $unit,
                1,
                (float) $this->input('unit_cost', 0)
            );

            $existing = InventoryTransaction::query()
                ->where('medicine_id', $this->input('medicine_id'))
                ->where('batch_no', $batchNo)
                ->where('type', 'stock_in')
                ->first();

            if ($existing && abs((float) $existing->unit_cost - $converted['base_unit_cost']) > 0.0001) {
                $validator->errors()->add(
                    'batch_no',
                    'A batch with this number already exists for this medicine with a different unit cost.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'batch_no.required'    => 'Batch number is required for every stock entry.',
            'expiry_date.required' => 'Expiry date is required for every stock entry.',
            'expiry_date.after'    => 'Expiry date must be a future date.',
        ];
    }
}
