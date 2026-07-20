<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Visit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IpdDraftBillService
{
    /**
     * Ensure the IPD visit has a single draft bill for the stay.
     * Idempotent — ward transfers / re-admits must not create another draft.
     */
    public static function ensureForVisit(Visit $visit): ?Bill
    {
        if ($visit->visit_type !== 'ipd') {
            return null;
        }

        $existing = Bill::query()
            ->where('visit_id', $visit->id)
            ->where('bill_type', 'ipd')
            ->where('status', 'draft')
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::connection('tenant')->transaction(function () use ($visit) {
            $bill = Bill::create([
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->id,
                'bill_number' => static::generateBillNumber(),
                'bill_date' => now()->toDateString(),
                'bill_type' => 'ipd',
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_type' => 'fixed',
                'discount_percentage' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
                'paid_amount' => 0,
                'due_amount' => 0,
                'status' => 'draft',
                'notes' => 'IPD draft bill — running charges for admission ' . ($visit->visit_no ?? ''),
                'created_by' => Auth::id(),
            ]);

            return $bill;
        });
    }

    private static function generateBillNumber(): string
    {
        $prefix = 'BILL-' . date('Y') . '-';

        $lastNumber = Bill::where('bill_number', 'like', $prefix . '%')
            ->selectRaw('MAX(CAST(SUBSTRING(bill_number, ?) AS UNSIGNED)) as max_num', [strlen($prefix) + 1])
            ->value('max_num');

        $nextNumber = ($lastNumber ?? 0) + 1;

        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
