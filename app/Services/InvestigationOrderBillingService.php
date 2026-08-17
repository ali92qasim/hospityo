<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\ImagingOrder;
use App\Models\LabOrder;
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
    public static function syncOrderToBill(LabOrder|ImagingOrder $order): void
    {
        if (! $order->visit_id) {
            return;
        }

        try {
            $isLab = $order instanceof LabOrder;
            $order->loadMissing($isLab
                ? ['visit.primaryDoctor', 'items.labTest']
                : ['visit.primaryDoctor', 'items.imagingStudy']
            );

            $visit = $order->visit;
            $orderingDoctorId = $order->doctor_id
                ?? ($visit?->visit_type === 'ipd'
                    ? $visit->primaryDoctor?->doctor_id
                    : $visit?->doctor_id);

            if (! $orderingDoctorId || $order->items->isEmpty()) {
                return;
            }

            DB::connection('tenant')->transaction(function () use ($order, $visit, $isLab) {
                [$bill, $isNewBill] = static::resolveBillForVisit($visit);

                foreach ($order->items as $item) {
                    $catalog = $isLab ? $item->labTest : $item->imagingStudy;
                    if (! $catalog) {
                        continue;
                    }

                    $bill->billItems()->create([
                        'lab_test_id' => $isLab ? $catalog->id : null,
                        'imaging_study_id' => $isLab ? null : $catalog->id,
                        'item_category' => $isLab ? 'lab' : 'imaging',
                        'description' => $catalog->name,
                        'quantity' => $item->quantity ?? 1,
                        'unit_price' => $catalog->price,
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
                        'Diagnostic order charges added'
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
            'notes'               => 'Auto-created from diagnostic order (visit '.$visit->visit_no.')',
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
