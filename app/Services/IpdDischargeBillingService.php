<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Visit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IpdDischargeBillingService
{
    /**
     * Finalize the IPD draft bill at discharge:
     * 1. Convert draft → formal invoice and post AR/revenue
     * 2. Apply admission advances against the invoice
     * 3. Refund leftover advance credit (if any)
     * 4. Optionally record an additional settlement payment for remaining due
     *
     * @return array{bill: Bill, advances_applied: float, refund_amount: float, amount_due: float, additional_payment: float}
     */
    public static function finalizeForDischarge(
        Visit $visit,
        ?string $refundMethod = null,
        float $additionalPaymentAmount = 0,
        ?string $additionalPaymentMethod = null,
    ): array {
        if ($visit->visit_type !== 'ipd') {
            throw new RuntimeException('Discharge billing is only available for IPD visits.');
        }

        $admission = $visit->admission;
        if (! $admission) {
            throw new RuntimeException('Admission record not found for this visit.');
        }

        if ($admission->status === 'discharged') {
            throw new RuntimeException('This admission has already been discharged.');
        }

        $bill = IpdDraftBillService::ensureForVisit($visit);
        if (! $bill) {
            throw new RuntimeException('Unable to locate or create the IPD draft bill.');
        }

        $bill->load('billItems');
        $bill->calculateTotals();

        $totalAdvances = (float) $admission->advances()->sum('amount');
        $billTotal = (float) $bill->total_amount;
        $applyAmount = round(min($totalAdvances, $billTotal), 2);
        $refundAmount = round(max(0, $totalAdvances - $billTotal), 2);
        $amountDueBeforeExtra = round(max(0, $billTotal - $totalAdvances), 2);

        if ($refundAmount > 0 && blank($refundMethod)) {
            throw new RuntimeException('A refund method is required because advance credit exceeds the final bill.');
        }

        $additionalPaymentAmount = round(max(0, $additionalPaymentAmount), 2);
        if ($additionalPaymentAmount > $amountDueBeforeExtra) {
            throw new RuntimeException('Additional payment cannot exceed the remaining amount due.');
        }

        if ($additionalPaymentAmount > 0 && blank($additionalPaymentMethod)) {
            throw new RuntimeException('A payment method is required for the additional settlement payment.');
        }

        return DB::connection('tenant')->transaction(function () use (
            $visit,
            $admission,
            $bill,
            $applyAmount,
            $refundAmount,
            $refundMethod,
            $additionalPaymentAmount,
            $additionalPaymentMethod,
            $amountDueBeforeExtra,
        ) {
            // 1. Finalize draft → formal invoice
            $bill->status = 'pending';
            $bill->bill_date = now()->toDateString();
            $bill->notes = trim(($bill->notes ? $bill->notes."\n" : '').'Finalized at IPD discharge on '.now()->toDateTimeString());
            $bill->save();

            AccountingService::postBillEntry($bill->fresh(['billItems', 'patient']));

            // 2. Apply advances (liability → settle AR) — no cash movement
            if ($applyAmount > 0) {
                $payment = $bill->payments()->create([
                    'payment_date' => now()->toDateString(),
                    'amount' => $applyAmount,
                    'payment_method' => 'advance',
                    'reference_number' => 'ADV-'.$admission->id,
                    'notes' => 'Applied from admission advances',
                    'received_by' => Auth::id(),
                ]);

                $bill->paid_amount = round((float) $bill->paid_amount + $applyAmount, 2);
                $bill->due_amount = max(0, round((float) $bill->total_amount - (float) $bill->paid_amount, 2));
                $bill->status = $bill->paid_amount >= $bill->total_amount ? 'paid' : 'partial';
                $bill->save();

                AccountingService::postAdvanceApplicationEntry($payment);
            }

            // 3. Refund leftover advance credit
            if ($refundAmount > 0) {
                AccountingService::postAdvanceRefundEntry(
                    $admission,
                    $refundAmount,
                    $refundMethod,
                    Auth::id()
                );

                $admission->update([
                    'refund_amount' => $refundAmount,
                    'refund_method' => $refundMethod,
                    'refunded_at' => now(),
                    'refunded_by' => Auth::id(),
                ]);
            }

            // 4. Optional additional payment for remaining due
            if ($additionalPaymentAmount > 0) {
                $extraPayment = $bill->payments()->create([
                    'payment_date' => now()->toDateString(),
                    'amount' => $additionalPaymentAmount,
                    'payment_method' => $additionalPaymentMethod,
                    'reference_number' => null,
                    'notes' => 'Settlement payment at IPD discharge',
                    'received_by' => Auth::id(),
                ]);

                $bill->paid_amount = round((float) $bill->paid_amount + $additionalPaymentAmount, 2);
                $bill->due_amount = max(0, round((float) $bill->total_amount - (float) $bill->paid_amount, 2));
                $bill->status = $bill->paid_amount >= $bill->total_amount ? 'paid' : 'partial';
                $bill->save();

                AccountingService::postPaymentEntry($extraPayment);
            }

            $bill->refresh();
            DoctorShareService::calculate($bill);

            return [
                'bill' => $bill,
                'advances_applied' => $applyAmount,
                'refund_amount' => $refundAmount,
                'amount_due' => (float) $bill->due_amount,
                'additional_payment' => $additionalPaymentAmount,
            ];
        });
    }

    /**
     * Preview settlement numbers for the discharge UI (no writes).
     */
    public static function preview(Visit $visit): array
    {
        $admission = $visit->admission;
        $bill = IpdDraftBillService::resolveForVisit($visit)
            ?? IpdDraftBillService::ensureForVisit($visit);

        $totalAdvances = (float) ($admission?->advances()->sum('amount') ?? 0);
        $billTotal = (float) ($bill?->total_amount ?? 0);

        return [
            'bill' => $bill,
            'total_advances' => $totalAdvances,
            'bill_total' => $billTotal,
            'advances_applied' => round(min($totalAdvances, $billTotal), 2),
            'refund_amount' => round(max(0, $totalAdvances - $billTotal), 2),
            'amount_due' => round(max(0, $billTotal - $totalAdvances), 2),
            'available_credit' => round($totalAdvances - $billTotal, 2),
        ];
    }
}
