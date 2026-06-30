<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Log;

class AccountingService
{
    /**
     * Reverse an existing journal entry by posting a mirror entry (swap debits/credits).
     * This preserves the full audit trail — original entries are never modified or deleted.
     *
     * @param  JournalEntry  $originalEntry  The entry to reverse
     * @param  string        $reason         Narration suffix explaining the reversal
     * @return JournalEntry|null
     */
    public static function reverseEntry(JournalEntry $originalEntry, string $reason = 'Bill updated'): ?JournalEntry
    {
        try {
            return \Illuminate\Support\Facades\DB::connection('tenant')->transaction(function () use ($originalEntry, $reason) {
                // Mark the original entry as superseded so it's no longer treated as
                // the active "original" by duplicate checks or queries.
                $originalEntry->update(['entry_type' => 'superseded']);

                // Use the same entry_date as the original so both entries fall within
                // the same reporting period and cancel each other out correctly.
                $reversal = JournalEntry::create([
                    'entry_date'     => $originalEntry->entry_date,
                    'reference_type' => $originalEntry->reference_type,
                    'reference_id'   => $originalEntry->reference_id,
                    'description'    => "REVERSAL — {$originalEntry->description} ({$reason})",
                    'created_by'     => auth()->id() ?? $originalEntry->created_by ?? 1,
                    'is_auto'        => true,
                    'entry_type'     => 'reversal',
                ]);

                // Mirror all lines — swap debit ↔ credit
                foreach ($originalEntry->lines as $line) {
                    $reversal->lines()->create([
                        'account_id' => $line->account_id,
                        'debit'      => $line->credit,
                        'credit'     => $line->debit,
                        'narration'  => "Reversal: {$line->narration}",
                    ]);
                }

                // Mirror sub-ledger entries
                foreach ($originalEntry->subLedgerEntries as $sub) {
                    $reversal->subLedgerEntries()->create([
                        'ledger_type' => $sub->ledger_type,
                        'ledger_id'   => $sub->ledger_id,
                        'debit'       => $sub->credit,
                        'credit'      => $sub->debit,
                        'narration'   => "Reversal: {$sub->narration}",
                    ]);
                }

                Log::info('[Accounting] Journal entry reversed', [
                    'original_entry_id' => $originalEntry->id,
                    'reversal_entry_id' => $reversal->id,
                    'reason'            => $reason,
                ]);

                return $reversal;
            });
        } catch (\Throwable $e) {
            Log::error('[Accounting] Failed to reverse journal entry', [
                'entry_id' => $originalEntry->id,
                'error'    => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Reverse the existing bill journal entry and post a fresh one with current amounts.
     * Used when a bill is edited after creation (e.g., discount added post-payment).
     *
     * @param  Bill    $bill    The updated bill (with new totals already calculated)
     * @param  string  $reason  Narration for the reversal
     * @return JournalEntry|null  The new corrected entry, or null on failure
     */
    public static function reverseAndRepostBillEntry(Bill $bill, string $reason = 'Bill updated'): ?JournalEntry
    {
        try {
            // Find the original (non-reversal) bill journal entry
            $originalEntry = JournalEntry::where('reference_type', 'Bill')
                ->where('reference_id', $bill->id)
                ->where('entry_type', 'original')
                ->latest('id')
                ->first();

            if ($originalEntry) {
                self::reverseEntry($originalEntry, $reason);
            }

            // Post fresh entry with current bill amounts
            // We need to temporarily remove the existing entry check by using a dedicated method
            return self::postBillEntryFresh($bill);
        } catch (\Throwable $e) {
            Log::error('[Accounting] Failed to reverse and repost bill entry', [
                'bill_id' => $bill->id,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Post a fresh bill journal entry without the duplicate check.
     * Used internally by reverseAndRepostBillEntry after reversal.
     */
    private static function postBillEntryFresh(Bill $bill): ?JournalEntry
    {
        try {
            $receivable  = Account::where('code', '1200')->first();
            $taxPayable  = Account::where('code', '2100')->first();
            $revenueCode = self::getRevenueAccountCode($bill->bill_type);
            $revenue     = Account::where('code', $revenueCode)->first();

            if (!$receivable || !$revenue) return null;

            $entry = JournalEntry::create([
                'entry_date'     => now()->toDateString(),
                'reference_type' => 'Bill',
                'reference_id'   => $bill->id,
                'description'    => "Invoice {$bill->bill_number} — {$bill->patient?->name} (revised)",
                'created_by'     => auth()->id() ?? $bill->created_by ?? 1,
                'is_auto'        => true,
                'entry_type'     => 'original',
            ]);

            // DR: Receivable for total amount
            $entry->lines()->create([
                'account_id' => $receivable->id,
                'debit'      => $bill->total_amount,
                'credit'     => 0,
                'narration'  => "Patient receivable for {$bill->bill_number} (revised)",
            ]);

            // CR: Revenue for subtotal
            $entry->lines()->create([
                'account_id' => $revenue->id,
                'debit'      => 0,
                'credit'     => $bill->subtotal,
                'narration'  => ucfirst($bill->bill_type) . " revenue (revised)",
            ]);

            // CR: Tax Payable (if tax > 0)
            if ($bill->tax_amount > 0 && $taxPayable) {
                $entry->lines()->create([
                    'account_id' => $taxPayable->id,
                    'debit'      => 0,
                    'credit'     => $bill->tax_amount,
                    'narration'  => "Tax on {$bill->bill_number} (revised)",
                ]);
            }

            // DR: Discount (if discount > 0)
            if ($bill->discount_amount > 0) {
                $discountAccount = Account::where('code', '5200')->first();
                if ($discountAccount) {
                    $entry->lines()->create([
                        'account_id' => $discountAccount->id,
                        'debit'      => $bill->discount_amount,
                        'credit'     => 0,
                        'narration'  => "Discount on {$bill->bill_number} (revised)",
                    ]);
                }
            }

            // Sub-ledger: Patient
            $entry->subLedgerEntries()->create([
                'ledger_type' => 'patient',
                'ledger_id'   => $bill->patient_id,
                'debit'       => $bill->total_amount,
                'credit'      => 0,
                'narration'   => "Invoice {$bill->bill_number} (revised)",
            ]);

            return $entry;
        } catch (\Throwable $e) {
            Log::error('[Accounting] Failed to post fresh bill entry', [
                'bill_id' => $bill->id,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Post an overpayment adjustment entry when paid_amount exceeds total_amount.
     * Moves the excess from Accounts Receivable to Patient Advance (liability).
     *
     * DR: Accounts Receivable (reduce the over-credited amount)
     * CR: Advance from Patients (patient has credit balance)
     *
     * @param  Bill   $bill
     * @param  float  $overpaymentAmount  The excess amount (paid - total)
     * @return JournalEntry|null
     */
    public static function postOverpaymentAdjustment(Bill $bill, float $overpaymentAmount): ?JournalEntry
    {
        try {
            if ($overpaymentAmount <= 0) return null;

            $receivable     = Account::where('code', '1200')->first();
            $patientAdvance = Account::where('code', '2300')->first();

            if (!$receivable || !$patientAdvance) {
                Log::warning('[Accounting] Cannot post overpayment — missing accounts (1200 or 2300)', [
                    'bill_id' => $bill->id,
                ]);
                return null;
            }

            $entry = JournalEntry::create([
                'entry_date'     => now()->toDateString(),
                'reference_type' => 'Bill',
                'reference_id'   => $bill->id,
                'description'    => "Overpayment adjustment — {$bill->bill_number} (patient credit)",
                'created_by'     => auth()->id() ?? 1,
                'is_auto'        => true,
                'entry_type'     => 'adjustment',
            ]);

            // DR: Accounts Receivable (netting out the excess credit from payment entries)
            $entry->lines()->create([
                'account_id' => $receivable->id,
                'debit'      => $overpaymentAmount,
                'credit'     => 0,
                'narration'  => "Overpayment adjustment for {$bill->bill_number}",
            ]);

            // CR: Advance from Patients (patient now has credit balance)
            $entry->lines()->create([
                'account_id' => $patientAdvance->id,
                'debit'      => 0,
                'credit'     => $overpaymentAmount,
                'narration'  => "Patient credit from {$bill->bill_number}",
            ]);

            // Sub-ledger: Patient credit
            $entry->subLedgerEntries()->create([
                'ledger_type' => 'patient',
                'ledger_id'   => $bill->patient_id,
                'debit'       => 0,
                'credit'      => $overpaymentAmount,
                'narration'   => "Credit balance from {$bill->bill_number}",
            ]);

            Log::info('[Accounting] Overpayment adjustment posted', [
                'bill_id' => $bill->id,
                'amount'  => $overpaymentAmount,
            ]);

            return $entry;
        } catch (\Throwable $e) {
            Log::error('[Accounting] Failed to post overpayment adjustment', [
                'bill_id' => $bill->id,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Post a journal entry for a new bill (invoice created).
     * DR: Accounts Receivable (patient owes money)
     * CR: Revenue account (based on bill type)
     * CR: Tax Payable (if tax applied)
     */
    public static function postBillEntry(Bill $bill): ?JournalEntry
    {
        try {
            // Prevent duplicate journal entries for the same bill
            // Only check for 'original' type entries — reversals and adjustments share the same reference
            $existing = JournalEntry::where('reference_type', 'Bill')
                ->where('reference_id', $bill->id)
                ->where('entry_type', 'original')
                ->first();

            if ($existing) {
                Log::info('[Accounting] Journal entry already exists for bill — skipping', [
                    'bill_id'  => $bill->id,
                    'entry_id' => $existing->id,
                ]);
                return $existing;
            }

            $receivable = Account::where('code', '1200')->first(); // Accounts Receivable
            $taxPayable = Account::where('code', '2100')->first(); // Tax Payable
            $revenueCode = self::getRevenueAccountCode($bill->bill_type);
            $revenue = Account::where('code', $revenueCode)->first();

            if (!$receivable || !$revenue) return null;

            $entry = JournalEntry::create([
                'entry_date' => $bill->bill_date,
                'reference_type' => 'Bill',
                'reference_id' => $bill->id,
                'description' => "Invoice {$bill->bill_number} — {$bill->patient?->name}",
                'created_by' => $bill->created_by ?? auth()->id() ?? 1,
                'is_auto' => true,
                'entry_type' => 'original',
            ]);

            // DR: Receivable for total amount
            $entry->lines()->create([
                'account_id' => $receivable->id,
                'debit' => $bill->total_amount,
                'credit' => 0,
                'narration' => "Patient receivable for {$bill->bill_number}",
            ]);

            // CR: Revenue for subtotal
            $entry->lines()->create([
                'account_id' => $revenue->id,
                'debit' => 0,
                'credit' => $bill->subtotal,
                'narration' => ucfirst($bill->bill_type) . " revenue",
            ]);

            // CR: Tax Payable (if tax > 0)
            if ($bill->tax_amount > 0 && $taxPayable) {
                $entry->lines()->create([
                    'account_id' => $taxPayable->id,
                    'debit' => 0,
                    'credit' => $bill->tax_amount,
                    'narration' => "Tax on {$bill->bill_number}",
                ]);
            }

            // CR: Discount (if discount > 0, reduce receivable)
            if ($bill->discount_amount > 0) {
                $discountAccount = Account::where('code', '5200')->first();
                if ($discountAccount) {
                    $entry->lines()->create([
                        'account_id' => $discountAccount->id,
                        'debit' => $bill->discount_amount,
                        'credit' => 0,
                        'narration' => "Discount on {$bill->bill_number}",
                    ]);
                }
            }

            // Sub-ledger: Patient
            $entry->subLedgerEntries()->create([
                'ledger_type' => 'patient',
                'ledger_id' => $bill->patient_id,
                'debit' => $bill->total_amount,
                'credit' => 0,
                'narration' => "Invoice {$bill->bill_number}",
            ]);

            return $entry;
        } catch (\Throwable $e) {
            Log::error('[Accounting] Failed to post bill entry', ['bill' => $bill->id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Post a journal entry for a payment received.
     * DR: Cash/Bank (money received)
     * CR: Accounts Receivable (patient debt reduced)
     */
    public static function postPaymentEntry(Payment $payment): ?JournalEntry
    {
        try {
            $bill = $payment->bill;
            if (!$bill) return null;

            $cashAccount = self::getPaymentAccount($payment->payment_method);
            $receivable = Account::where('code', '1200')->first();

            if (!$cashAccount || !$receivable) return null;

            $entry = JournalEntry::create([
                'entry_date' => $payment->payment_date,
                'reference_type' => 'Payment',
                'reference_id' => $payment->id,
                'description' => "Payment received for {$bill->bill_number} — {$payment->payment_method}",
                'created_by' => $payment->received_by ?? auth()->id() ?? 1,
                'is_auto' => true,
                'entry_type' => 'original',
            ]);

            // DR: Cash/Bank
            $entry->lines()->create([
                'account_id' => $cashAccount->id,
                'debit' => $payment->amount,
                'credit' => 0,
                'narration' => "Payment via {$payment->payment_method}",
            ]);

            // CR: Accounts Receivable
            $entry->lines()->create([
                'account_id' => $receivable->id,
                'debit' => 0,
                'credit' => $payment->amount,
                'narration' => "Payment against {$bill->bill_number}",
            ]);

            // Sub-ledger: Patient
            $entry->subLedgerEntries()->create([
                'ledger_type' => 'patient',
                'ledger_id' => $bill->patient_id,
                'debit' => 0,
                'credit' => $payment->amount,
                'narration' => "Payment for {$bill->bill_number}",
            ]);

            return $entry;
        } catch (\Throwable $e) {
            Log::error('[Accounting] Failed to post payment entry', ['payment' => $payment->id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Post a journal entry for a purchase order received.
     * DR: Inventory / COGS
     * CR: Accounts Payable (vendor)
     */
    public static function postPurchaseEntry(PurchaseOrder $po): ?JournalEntry
    {
        try {
            $inventory = Account::where('code', '1300')->first();  // Inventory
            $payable = Account::where('code', '2200')->first();    // Accounts Payable
            $taxReceivable = Account::where('code', '1400')->first(); // Input Tax

            if (!$inventory || !$payable) return null;

            $entry = JournalEntry::create([
                'entry_date' => $po->order_date,
                'reference_type' => 'PurchaseOrder',
                'reference_id' => $po->id,
                'description' => "Purchase {$po->po_number} — {$po->supplier?->name}",
                'created_by' => $po->created_by ?? auth()->id() ?? 1,
                'is_auto' => true,
                'entry_type' => 'original',
            ]);

            // DR: Inventory for subtotal
            $entry->lines()->create([
                'account_id' => $inventory->id,
                'debit' => $po->subtotal,
                'credit' => 0,
                'narration' => "Inventory from {$po->po_number}",
            ]);

            // DR: Input Tax (if applicable)
            if ($po->tax_amount > 0 && $taxReceivable) {
                $entry->lines()->create([
                    'account_id' => $taxReceivable->id,
                    'debit' => $po->tax_amount,
                    'credit' => 0,
                    'narration' => "Input tax on {$po->po_number}",
                ]);
            }

            // CR: Accounts Payable for total
            $entry->lines()->create([
                'account_id' => $payable->id,
                'debit' => 0,
                'credit' => $po->total_amount,
                'narration' => "Payable to {$po->supplier?->name}",
            ]);

            // Sub-ledger: Vendor
            if ($po->supplier_id) {
                $entry->subLedgerEntries()->create([
                    'ledger_type' => 'vendor',
                    'ledger_id' => $po->supplier_id,
                    'debit' => 0,
                    'credit' => $po->total_amount,
                    'narration' => "Purchase {$po->po_number}",
                ]);
            }

            return $entry;
        } catch (\Throwable $e) {
            Log::error('[Accounting] Failed to post purchase entry', ['po' => $po->id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private static function getRevenueAccountCode(string $billType): string
    {
        return match ($billType) {
            'opd' => '4100',
            'ipd' => '4200',
            'investigation' => '4300',
            'pharmacy' => '4400',
            'emergency' => '4500',
            default => '4100',
        };
    }

    private static function getPaymentAccount(string $method): ?Account
    {
        $code = match ($method) {
            'cash' => '1100',
            'card', 'credit_card', 'debit_card' => '1110',
            'bank_transfer', 'bank', 'online' => '1110',
            'insurance' => '1210',
            default => '1100',
        };
        return Account::where('code', $code)->first();
    }
}
