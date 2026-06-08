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
     * Post a journal entry for a new bill (invoice created).
     * DR: Accounts Receivable (patient owes money)
     * CR: Revenue account (based on bill type)
     * CR: Tax Payable (if tax applied)
     */
    public static function postBillEntry(Bill $bill): ?JournalEntry
    {
        try {
            // Prevent duplicate journal entries for the same bill
            $existing = JournalEntry::where('reference_type', 'Bill')
                ->where('reference_id', $bill->id)
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
