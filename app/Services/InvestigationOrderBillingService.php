<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\InvestigationOrder;
use App\Models\Visit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvestigationOrderBillingService
{
    /**
     * Add investigation order charges to the visit bill and recalculate doctor share.
     * OPD/emergency: pending bill + share calculated immediately.
     * IPD: draft bill only — share runs at discharge.
     */
    public static function syncOrderToBill(InvestigationOrder $order): void
    {
        if (! $order->visit_id) {
            return;
        }

        try {
            $order->loadMissing(['visit', 'items.investigation']);

            $visit = $order->visit;
            if (! $visit?->doctor_id || $order->items->isEmpty()) {
                return;
            }

            DB::connection('tenant')->transaction(function () use ($order, $visit) {
                [$bill, $isNewBill] = static::resolveBillForVisit($visit);

                foreach ($order->items as $item) {
                    $investigation = $item->investigation;
                    if (! $investigation) {
                        continue;
                    }

                    $bill->billItems()->create([
                        'investigation_id' => $investigation->id,
                        'item_category'    => 'investigation',
                        'description'      => $investigation->name,
                        'quantity'         => $item->quantity ?? 1,
                        'unit_price'       => $investigation->price,
                    ]);
                }

                $bill->load('billItems');
                $bill->calculateTotals();

                if ($visit->visit_type === 'ipd') {
                    return;
                }

                if ($isNewBill) {
                    AccountingService::postBillEntry($bill);
                } else {
                    AccountingService::reverseAndRepostBillEntry(
                        $bill,
                        'Investigation order charges added'
                    );
                }

                DoctorShareService::calculate($bill);
            });
        } catch (\Throwable $e) {
            Log::error('[InvestigationOrderBilling] Failed to sync order to bill', [
                'investigation_order_id' => $order->id,
                'visit_id'               => $order->visit_id,
                'error'                  => $e->getMessage(),
            ]);
        }
    }

    /** @return array{0: Bill, 1: bool} */
    private static function resolveBillForVisit(Visit $visit): array
    {
        if ($visit->visit_type === 'ipd') {
            $bill = IpdDraftBillService::ensureForVisit($visit);

            return [$bill, false];
        }

        $billType = in_array($visit->visit_type, ['opd', 'emergency'], true)
            ? $visit->visit_type
            : 'opd';

        $existing = Bill::query()
            ->where('visit_id', $visit->id)
            ->where('bill_type', $billType)
            ->whereIn('status', ['pending', 'partial'])
            ->latest('id')
            ->first();

        if ($existing) {
            return [$existing, false];
        }

        $bill = Bill::create([
            'patient_id'          => $visit->patient_id,
            'visit_id'            => $visit->id,
            'bill_number'         => static::generateBillNumber(),
            'bill_date'           => now()->toDateString(),
            'bill_type'           => $billType,
            'subtotal'            => 0,
            'tax_amount'          => 0,
            'discount_type'       => 'fixed',
            'discount_percentage' => 0,
            'discount_amount'     => 0,
            'total_amount'        => 0,
            'paid_amount'         => 0,
            'due_amount'          => 0,
            'status'              => 'pending',
            'notes'               => 'Auto-created from investigation order (visit '.$visit->visit_no.')',
            'created_by'          => Auth::id() ?? 1,
        ]);

        return [$bill, true];
    }

    private static function generateBillNumber(): string
    {
        $prefix = 'BILL-'.date('Y').'-';

        $lastNumber = Bill::where('bill_number', 'like', $prefix.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(bill_number, ?) AS UNSIGNED)) as max_num', [strlen($prefix) + 1])
            ->value('max_num');

        $nextNumber = ($lastNumber ?? 0) + 1;

        return $prefix.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
