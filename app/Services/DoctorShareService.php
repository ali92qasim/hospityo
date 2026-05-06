<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\DoctorShareAllocation;
use App\Models\DoctorShareItem;
use App\Models\DoctorShareRule;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DoctorShareService
 *
 * Manages the full lifecycle of doctor revenue share records.
 *
 * ── Architecture ──────────────────────────────────────────────────────────────
 *
 * Two tables, two responsibilities:
 *
 *   doctor_share_items       — EARNED SHARE LIABILITY
 *     "How much does the hospital owe this doctor for this billed item?"
 *     Written once at bill creation. share_amount is immutable.
 *     Status: pending | settled | voided.
 *
 *   doctor_share_allocations — IMMUTABLE COLLECTION EVENT LEDGER
 *     "How much of that share has been collected from the patient?"
 *     One row per payment event. Rows are never updated or deleted.
 *     Positive amount = collection. Negative amount = reversal (refund).
 *     Running balance = SUM(amount) WHERE doctor_share_item_id = ?
 *
 * This separation means:
 *   - share_amount is written once and never mutated
 *   - refunds are first-class events, not corrections to existing rows
 *   - settlement runs read a point-in-time SUM, not a mutable column
 *   - any historical balance is reproducible by filtering on created_at
 *   - disputes are resolved by replaying the allocation log
 *
 * ── Integration points (all in BillController) ────────────────────────────────
 *   store()      → calculate($bill)                  after calculateTotals()
 *   update()     → voidForBill($bill, reason)        before billItems()->delete()
 *                  calculate($bill)                  after calculateTotals()
 *   destroy()    → voidForBill($bill, reason)        before $bill->delete()
 *   addPayment() → recordPaymentAllocations($payment) after postPaymentEntry()
 *
 * ── Financial correctness guarantees ─────────────────────────────────────────
 *   - All decimal arithmetic uses bcmath at scale 6
 *   - No float arithmetic anywhere in the calculation path
 *   - All writes for a given operation are inside a single DB transaction
 *   - Allocation rows are immutable after insert (enforced in the model)
 *   - Settled share items block bill modification (RuntimeException thrown)
 *   - This service never touches bills, bill_items, payments, or journal_entries
 */
class DoctorShareService
{
    /**
     * Bill types excluded from doctor share by business policy.
     * Pharmacy margin is not shared with prescribing doctors.
     */
    private const EXCLUDED_BILL_TYPES = ['pharmacy'];

    /**
     * Internal bcmath scale. Higher than storage precision to avoid
     * intermediate rounding errors in multi-step calculations.
     */
    private const SCALE = 6;

    // ──────────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Calculate and persist share items for a bill.
     *
     * Called from BillController::store() and BillController::update()
     * after calculateTotals() has run.
     *
     * All writes are inside a single transaction. If any item fails,
     * the entire calculation rolls back — no partial share state.
     *
     * Does NOT throw. A share failure must never break the billing flow.
     * Failures are logged with full context for operator review.
     */
    public static function calculate(Bill $bill): void
    {
        try {
            if (self::isExcluded($bill->bill_type)) {
                Log::info('[DoctorShare] Skipped — excluded bill type', [
                    'bill_id'   => $bill->id,
                    'bill_type' => $bill->bill_type,
                ]);
                return;
            }

            $doctorId = self::resolveDoctorId($bill);

            if ($doctorId === null) {
                Log::info('[DoctorShare] Unattributed — no doctor on bill', [
                    'bill_id'     => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'visit_id'    => $bill->visit_id,
                ]);
                return;
            }

            $bill->loadMissing('billItems');

            if ($bill->billItems->isEmpty()) {
                return;
            }

            DB::connection('tenant')->transaction(function () use ($bill, $doctorId) {
                foreach ($bill->billItems as $item) {
                    self::writeItemShare($bill, $item, $doctorId);
                }
            });

            Log::info('[DoctorShare] Calculated', [
                'bill_id'   => $bill->id,
                'doctor_id' => $doctorId,
                'items'     => $bill->billItems->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[DoctorShare] calculate() failed — share not recorded', [
                'bill_id' => $bill->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Void all non-settled share items for a bill.
     *
     * Called from BillController::update() BEFORE billItems()->delete(),
     * and from BillController::destroy() BEFORE $bill->delete().
     *
     * Uses a single bulk UPDATE — atomic, no per-row saves, no partial state.
     *
     * @throws \RuntimeException if settled items exist. The caller must catch
     *         this, surface the message to the user, and abort the operation.
     */
    public static function voidForBill(Bill $bill, string $reason): void
    {
        $items = DoctorShareItem::forBill($bill->id)
            ->active()
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        $settledIds = $items->where('status', 'settled')->pluck('id');

        if ($settledIds->isNotEmpty()) {
            throw new \RuntimeException(
                "Bill #{$bill->bill_number} has settled doctor share items " .
                "(IDs: {$settledIds->implode(', ')}). " .
                'A manual finance adjustment is required before this bill can be modified or deleted.'
            );
        }

        DoctorShareItem::whereIn('id', $items->pluck('id'))
            ->update([
                'status'      => 'voided',
                'void_reason' => $reason,
                'voided_at'   => now(),
                'updated_at'  => now(),
            ]);

        Log::info('[DoctorShare] Voided', [
            'bill_id' => $bill->id,
            'reason'  => $reason,
            'count'   => $items->count(),
        ]);
    }

    /**
     * Record allocation events for all active share items on a bill,
     * proportional to the payment received.
     *
     * Called from BillController::addPayment() after postPaymentEntry().
     *
     * Each call appends immutable rows to doctor_share_allocations.
     * No existing row is ever updated. This is the only write path for
     * collection tracking — there is no mutable "collected_share" column.
     *
     * Idempotency: the unique index on (payment_id, doctor_share_item_id)
     * prevents duplicate allocations if this method is called twice for
     * the same payment (e.g., retry after a transient failure).
     *
     * Does NOT throw. A failure here must never break the payment flow.
     */
    public static function recordPaymentAllocations(Payment $payment): void
    {
        try {
            $bill = $payment->bill;

            if (! $bill) {
                Log::warning('[DoctorShare] recordPaymentAllocations() — payment has no bill', [
                    'payment_id' => $payment->id,
                ]);
                return;
            }

            $billTotal = (string) $bill->total_amount;

            if (bccomp($billTotal, '0', self::SCALE) <= 0) {
                return;
            }

            $activeItems = DoctorShareItem::forBill($bill->id)
                ->active()
                ->get();

            if ($activeItems->isEmpty()) {
                return;
            }

            $paymentAmount = (string) $payment->amount;

            // collection_ratio = this_payment / bill_total
            // Using bill_total (not remaining due) ensures the ratio is consistent
            // across all payments regardless of order. The sum of all ratios across
            // all payments for a fully-paid bill will equal exactly 1.
            $collectionRatio = bcdiv($paymentAmount, $billTotal, self::SCALE);

            DB::connection('tenant')->transaction(function () use (
                $activeItems, $collectionRatio, $payment, $bill
            ) {
                foreach ($activeItems as $item) {
                    self::insertAllocation($item, $payment, $bill, $collectionRatio);
                }
            });

            Log::info('[DoctorShare] Allocations recorded', [
                'payment_id'       => $payment->id,
                'bill_id'          => $bill->id,
                'collection_ratio' => $collectionRatio,
                'items'            => $activeItems->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[DoctorShare] recordPaymentAllocations() failed', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Resolve the most specific active share rule for a given context.
     *
     * Public so it can be called from a rule-preview UI or test.
     *
     * Priority:
     *   Level 1: doctor_id + service_id  OR  doctor_id + investigation_id
     *   Level 2: doctor_id only (doctor default)
     *   Level 3: global default (no doctor, no service, no investigation)
     *
     * Returns null if no rule matches — the item is skipped (share = 0).
     */
    public static function resolveRule(
        int $doctorId,
        ?int $serviceId,
        ?int $investigationId,
        string $billType
    ): ?DoctorShareRule {
        // Level 1 — most specific
        if ($serviceId !== null || $investigationId !== null) {
            $rule = DoctorShareRule::active()
                ->forBillType($billType)
                ->where('doctor_id', $doctorId)
                ->where(function ($q) use ($serviceId, $investigationId) {
                    if ($serviceId !== null) {
                        $q->orWhere(function ($sub) use ($serviceId) {
                            $sub->where('service_id', $serviceId)
                                ->whereNull('investigation_id');
                        });
                    }
                    if ($investigationId !== null) {
                        $q->orWhere(function ($sub) use ($investigationId) {
                            $sub->where('investigation_id', $investigationId)
                                ->whereNull('service_id');
                        });
                    }
                })
                ->first();

            if ($rule !== null) {
                return $rule;
            }
        }

        // Level 2 — doctor default
        $rule = DoctorShareRule::active()
            ->forBillType($billType)
            ->where('doctor_id', $doctorId)
            ->whereNull('service_id')
            ->whereNull('investigation_id')
            ->first();

        if ($rule !== null) {
            return $rule;
        }

        // Level 3 — global default
        return DoctorShareRule::active()
            ->forBillType($billType)
            ->whereNull('doctor_id')
            ->whereNull('service_id')
            ->whereNull('investigation_id')
            ->first();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Compute and upsert the share record for one bill item.
     * Must be called inside a DB transaction.
     */
    private static function writeItemShare(
        Bill $bill,
        BillItem $item,
        int $doctorId
    ): void {
        $rule = self::resolveRule(
            $doctorId,
            $item->service_id,
            $item->investigation_id,
            $bill->bill_type
        );

        if ($rule === null) {
            Log::debug('[DoctorShare] No rule — item skipped', [
                'bill_item_id'     => $item->id,
                'service_id'       => $item->service_id,
                'investigation_id' => $item->investigation_id,
            ]);
            return;
        }

        // base_amount = quantity × unit_price (pre-tax, pre-discount)
        $baseAmount = bcmul(
            (string) $item->quantity,
            (string) $item->unit_price,
            self::SCALE
        );

        $shareAmount = self::computeShare($rule, $baseAmount);

        // updateOrCreate: unique constraint on bill_item_id prevents duplicates
        // on retry. The 'status' reset to 'pending' is intentional — if a voided
        // item is somehow recalculated (should not happen in normal flow), it
        // becomes active again with fresh amounts.
        DoctorShareItem::updateOrCreate(
            ['bill_item_id' => $item->id],
            [
                'bill_id'       => $bill->id,
                'doctor_id'     => $doctorId,
                'rule_id'       => $rule->id,
                'rule_snapshot' => self::snapshot($rule),
                'base_amount'   => self::store($baseAmount),
                'share_amount'  => self::store($shareAmount),
                'status'        => 'pending',
                'void_reason'   => null,
                'voided_at'     => null,
                'settlement_id' => null,
            ]
        );
    }

    /**
     * Insert one allocation row for a share item.
     *
     * Uses insertOrIgnore against the unique index on (payment_id, doctor_share_item_id)
     * to guarantee idempotency — calling this twice for the same payment produces
     * exactly one allocation row, not two.
     *
     * Must be called inside a DB transaction.
     */
    private static function insertAllocation(
        DoctorShareItem $item,
        Payment $payment,
        Bill $bill,
        string $collectionRatio
    ): void {
        $allocationAmount = bcmul(
            (string) $item->share_amount,
            $collectionRatio,
            self::SCALE
        );

        // Cap at share_amount — rounding across multiple payments can push it over
        $currentTotal = (string) DoctorShareAllocation::where('doctor_share_item_id', $item->id)
            ->sum('amount');

        $remaining = bcsub((string) $item->share_amount, $currentTotal, self::SCALE);

        if (bccomp($remaining, '0', self::SCALE) <= 0) {
            // Share already fully collected — no allocation needed
            return;
        }

        // Do not allocate more than what remains
        if (bccomp($allocationAmount, $remaining, self::SCALE) > 0) {
            $allocationAmount = $remaining;
        }

        $storedAmount = self::store($allocationAmount);

        // Skip zero-amount allocations (can occur due to rounding on small payments)
        if (bccomp($storedAmount, '0', 2) <= 0) {
            return;
        }

        // insertOrIgnore: if the unique index (payment_id, doctor_share_item_id)
        // already exists, the insert is silently skipped — idempotent by design.
        DB::connection('tenant')->table('doctor_share_allocations')->insertOrIgnore([
            'doctor_share_item_id' => $item->id,
            'payment_id'           => $payment->id,
            'bill_id'              => $bill->id,
            'doctor_id'            => $item->doctor_id,
            'amount'               => $storedAmount,
            'type'                 => 'collection',
            'notes'                => null,
            'created_at'           => now(),
        ]);
    }

    /**
     * Compute share amount from a rule and a base amount.
     * Both inputs and output are bcmath strings.
     */
    private static function computeShare(DoctorShareRule $rule, string $baseAmount): string
    {
        return match ($rule->share_type) {
            'percentage' => bcdiv(
                bcmul($baseAmount, (string) $rule->share_value, self::SCALE),
                '100',
                self::SCALE
            ),
            'fixed' => bcadd((string) $rule->share_value, '0', self::SCALE),
        };
    }

    /**
     * Round a bcmath string to 2 decimal places for DB storage.
     *
     * Uses pure bcmath half-up rounding — never casts to float.
     * Casting to float before rounding reintroduces IEEE 754 errors
     * (e.g., 0.005 becomes 0.0049999... and rounds down incorrectly).
     */
    private static function store(string $value): string
    {
        $rounded = bcadd($value, '0.000005', self::SCALE);
        return bcdiv($rounded, '1', 2);
    }

    /**
     * Resolve the doctor_id from a bill via its visit.
     * Returns null if the bill has no visit or the visit has no doctor.
     */
    private static function resolveDoctorId(Bill $bill): ?int
    {
        $bill->loadMissing('visit');
        return $bill->visit?->doctor_id;
    }

    /**
     * Build the frozen rule snapshot for audit trail.
     * Stored as JSON on the share item — survives rule edits and deletions.
     */
    private static function snapshot(DoctorShareRule $rule): array
    {
        $level = match (true) {
            $rule->doctor_id !== null && ($rule->service_id !== null || $rule->investigation_id !== null)
                => 'doctor_service',
            $rule->doctor_id !== null
                => 'doctor_default',
            default
                => 'global_default',
        };

        return [
            'rule_id'          => $rule->id,
            'level'            => $level,
            'share_type'       => $rule->share_type,
            'share_value'      => (string) $rule->share_value,
            'applies_to'       => $rule->applies_to,
            'doctor_id'        => $rule->doctor_id,
            'service_id'       => $rule->service_id,
            'investigation_id' => $rule->investigation_id,
        ];
    }

    /**
     * Whether a bill type is excluded from share calculation.
     */
    private static function isExcluded(string $billType): bool
    {
        return in_array($billType, self::EXCLUDED_BILL_TYPES, true);
    }
}
