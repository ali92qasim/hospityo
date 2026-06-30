<?php

use App\Models\Account;
use App\Models\Bill;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'payedit@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($this->user);

    // Seed required accounts
    Account::create(['code' => '1100', 'name' => 'Cash in Hand', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '1110', 'name' => 'Bank Account', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '2100', 'name' => 'Tax Payable', 'type' => 'liability', 'is_system' => true]);
    Account::create(['code' => '2300', 'name' => 'Advance from Patients', 'type' => 'liability', 'is_system' => true]);
    Account::create(['code' => '4100', 'name' => 'OPD Revenue', 'type' => 'revenue', 'is_system' => true]);
    Account::create(['code' => '5200', 'name' => 'Discounts Given', 'type' => 'expense', 'is_system' => true]);

    $this->patient = Patient::create([
        'name' => 'Pay Edit Patient',
        'gender' => 'male',
        'age' => 30,
        'phone' => '03002222222',
    ]);

    $this->bill = Bill::create([
        'patient_id' => $this->patient->id,
        'bill_number' => 'BILL-PE-001',
        'bill_date' => now(),
        'bill_type' => 'opd',
        'subtotal' => 10000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 10000,
        'paid_amount' => 0,
        'due_amount' => 10000,
        'status' => 'pending',
        'created_by' => $this->user->id,
    ]);

    // Post bill entry
    AccountingService::postBillEntry($this->bill);
});

it('editing a payment reverses old entry and posts a new one', function () {
    // Create payment
    $payment = Payment::create([
        'bill_id' => $this->bill->id,
        'payment_date' => now(),
        'amount' => 5000,
        'payment_method' => 'cash',
        'received_by' => $this->user->id,
    ]);
    $this->bill->update(['paid_amount' => 5000, 'due_amount' => 5000, 'status' => 'partial']);
    AccountingService::postPaymentEntry($payment);

    // Verify initial state
    $originalEntries = JournalEntry::where('reference_type', 'Payment')
        ->where('reference_id', $payment->id)
        ->where('entry_type', 'original')
        ->count();
    expect($originalEntries)->toBe(1);

    // Simulate the edit (reverse + update + repost)
    DB::connection('tenant')->transaction(function () use ($payment) {
        $oldAmount = (float) $payment->amount;
        $newAmount = 3000.0;

        // Reverse old
        $entry = JournalEntry::where('reference_type', 'Payment')
            ->where('reference_id', $payment->id)
            ->where('entry_type', 'original')
            ->with(['lines', 'subLedgerEntries'])
            ->first();
        $reversal = AccountingService::reverseEntry($entry, 'payment_edited');
        expect($reversal)->not->toBeNull();

        // Update payment
        $payment->update(['amount' => $newAmount, 'payment_method' => 'card']);

        // Update bill
        $this->bill->paid_amount = $this->bill->paid_amount - $oldAmount + $newAmount;
        $this->bill->due_amount = max(0, $this->bill->total_amount - $this->bill->paid_amount);
        $this->bill->save();

        // Post new entry
        $newEntry = AccountingService::postPaymentEntry($payment);
        expect($newEntry)->not->toBeNull()
            ->and($newEntry->entry_type)->toBe('original');
    });

    // Verify final state
    $allEntries = JournalEntry::where('reference_type', 'Payment')
        ->where('reference_id', $payment->id)
        ->get();

    $superseded = $allEntries->where('entry_type', 'superseded')->count();
    $reversals = $allEntries->where('entry_type', 'reversal')->count();
    $originals = $allEntries->where('entry_type', 'original')->count();

    expect($superseded)->toBe(1)
        ->and($reversals)->toBe(1)
        ->and($originals)->toBe(1);

    // New entry should have the updated amount (3000 via card)
    $newOriginal = $allEntries->where('entry_type', 'original')->first();
    $cashLine = $newOriginal->lines->where('debit', '>', 0)->first();
    expect((float) $cashLine->debit)->toBe(3000.0);

    // Bill should reflect updated paid amount
    expect((float) $this->bill->fresh()->paid_amount)->toBe(3000.0)
        ->and((float) $this->bill->fresh()->due_amount)->toBe(7000.0);
});

it('editing a payment that triggered overpayment reverses adjustment and recomputes', function () {
    // Create overpaying payment (12000 on 10000 bill)
    $payment = Payment::create([
        'bill_id' => $this->bill->id,
        'payment_date' => now(),
        'amount' => 12000,
        'payment_method' => 'cash',
        'received_by' => $this->user->id,
    ]);
    $this->bill->update(['paid_amount' => 12000, 'due_amount' => 0, 'status' => 'paid']);
    AccountingService::postPaymentEntry($payment);
    AccountingService::postOverpaymentAdjustment($this->bill, 2000);

    // Verify adjustment exists
    $adjustments = JournalEntry::where('reference_type', 'Bill')
        ->where('reference_id', $this->bill->id)
        ->where('entry_type', 'adjustment')
        ->count();
    expect($adjustments)->toBe(1);

    // Assert ledger balances BEFORE edit:
    // Receivable (1200): DR 10000 (bill) - CR 12000 (payment) + DR 2000 (overpay adj) = 0
    // Cash (1100): DR 12000 (payment) = 12000
    // Advance from Patients (2300): CR 2000 (overpay adj) = 2000
    $receivable = Account::where('code', '1200')->first();
    $cash = Account::where('code', '1100')->first();
    $advance = Account::where('code', '2300')->first();

    $receivableBalanceBefore = $receivable->getBalance(null, null);
    $cashBalanceBefore = $cash->getBalance(null, null);
    $advanceBalanceBefore = $advance->getBalance(null, null);

    expect((float) $receivableBalanceBefore)->toBe(0.0)   // net zero: 10000 DR - 12000 CR + 2000 DR
        ->and((float) $cashBalanceBefore)->toBe(12000.0)
        ->and((float) $advanceBalanceBefore)->toBe(2000.0);

    // Edit payment down to 8000 (no longer overpaid)
    DB::connection('tenant')->transaction(function () use ($payment) {
        $oldAmount = (float) $payment->amount;
        $newAmount = 8000.0;

        // Reverse old payment entry
        $entry = JournalEntry::where('reference_type', 'Payment')
            ->where('reference_id', $payment->id)
            ->where('entry_type', 'original')
            ->with(['lines', 'subLedgerEntries'])
            ->first();
        AccountingService::reverseEntry($entry, 'payment_edited');

        // Reverse ALL overpayment adjustments for bill
        $overpaymentEntries = JournalEntry::where('reference_type', 'Bill')
            ->where('reference_id', $this->bill->id)
            ->where('entry_type', 'adjustment')
            ->where('description', 'like', 'Overpayment%')
            ->with(['lines', 'subLedgerEntries'])
            ->get();
        foreach ($overpaymentEntries as $adjEntry) {
            AccountingService::reverseEntry($adjEntry, 'payment_edited_recalc');
        }

        // Update payment
        $payment->update(['amount' => $newAmount]);

        // Recalculate bill
        $this->bill->paid_amount = $this->bill->paid_amount - $oldAmount + $newAmount;
        $this->bill->due_amount = max(0, $this->bill->total_amount - $this->bill->paid_amount);
        $this->bill->save();

        // Post new payment entry
        AccountingService::postPaymentEntry($payment);

        // Recompute overpayment (should be 0 now: 8000 < 10000)
        $overpayment = round((float) $this->bill->paid_amount - (float) $this->bill->total_amount, 2);
        if ($overpayment > 0) {
            AccountingService::postOverpaymentAdjustment($this->bill, $overpayment);
        }
    });

    // Verify: no active adjustment entries remain (old ones are superseded)
    $activeAdjustments = JournalEntry::where('reference_type', 'Bill')
        ->where('reference_id', $this->bill->id)
        ->where('entry_type', 'adjustment')
        ->count();
    expect($activeAdjustments)->toBe(0); // all superseded

    // Bill should now show 8000 paid, 2000 due
    $bill = $this->bill->fresh();
    expect((float) $bill->paid_amount)->toBe(8000.0)
        ->and((float) $bill->due_amount)->toBe(2000.0);

    // Assert ledger balances AFTER edit:
    // Receivable (1200): bill DR 10000, payment CR 8000 = 2000 (patient owes 2000)
    // Cash (1100): payment DR 8000 (net after reversal of 12000 + new 8000)
    // Advance (2300): 0 (overpay adjustment was reversed, no new one posted)
    $receivableBalanceAfter = $receivable->getBalance(null, null);
    $cashBalanceAfter = $cash->getBalance(null, null);
    $advanceBalanceAfter = $advance->getBalance(null, null);

    expect((float) $receivableBalanceAfter)->toBe(2000.0)  // 10000 DR - 8000 CR = 2000
        ->and((float) $cashBalanceAfter)->toBe(8000.0)     // net cash received
        ->and((float) $advanceBalanceAfter)->toBe(0.0);    // no overpayment
});

it('editing a payment within bounds does not touch adjustments', function () {
    // Create normal payment (5000 on 10000 bill — no overpayment)
    $payment = Payment::create([
        'bill_id' => $this->bill->id,
        'payment_date' => now(),
        'amount' => 5000,
        'payment_method' => 'cash',
        'received_by' => $this->user->id,
    ]);
    $this->bill->update(['paid_amount' => 5000, 'due_amount' => 5000, 'status' => 'partial']);
    AccountingService::postPaymentEntry($payment);

    // No adjustments should exist
    $adjustmentsBefore = JournalEntry::where('reference_type', 'Bill')
        ->where('reference_id', $this->bill->id)
        ->where('entry_type', 'adjustment')
        ->count();
    expect($adjustmentsBefore)->toBe(0);

    // Edit to 7000 (still within bounds)
    DB::connection('tenant')->transaction(function () use ($payment) {
        $oldAmount = (float) $payment->amount;
        $newAmount = 7000.0;

        $entry = JournalEntry::where('reference_type', 'Payment')
            ->where('reference_id', $payment->id)
            ->where('entry_type', 'original')
            ->with(['lines', 'subLedgerEntries'])
            ->first();
        AccountingService::reverseEntry($entry, 'payment_edited');

        // No adjustments to reverse (none exist)
        $overpaymentEntries = JournalEntry::where('reference_type', 'Bill')
            ->where('reference_id', $this->bill->id)
            ->where('entry_type', 'adjustment')
            ->where('description', 'like', 'Overpayment%')
            ->with(['lines', 'subLedgerEntries'])
            ->get();
        foreach ($overpaymentEntries as $adjEntry) {
            AccountingService::reverseEntry($adjEntry, 'payment_edited_recalc');
        }

        $payment->update(['amount' => $newAmount]);

        $this->bill->paid_amount = $this->bill->paid_amount - $oldAmount + $newAmount;
        $this->bill->due_amount = max(0, $this->bill->total_amount - $this->bill->paid_amount);
        $this->bill->save();

        AccountingService::postPaymentEntry($payment);

        $overpayment = round((float) $this->bill->paid_amount - (float) $this->bill->total_amount, 2);
        if ($overpayment > 0) {
            AccountingService::postOverpaymentAdjustment($this->bill, $overpayment);
        }
    });

    // Still no adjustments
    $adjustmentsAfter = JournalEntry::where('reference_type', 'Bill')
        ->where('reference_id', $this->bill->id)
        ->whereIn('entry_type', ['adjustment'])
        ->count();
    expect($adjustmentsAfter)->toBe(0);

    // Bill should show 7000 paid, 3000 due
    $bill = $this->bill->fresh();
    expect((float) $bill->paid_amount)->toBe(7000.0)
        ->and((float) $bill->due_amount)->toBe(3000.0);
});

it('postPaymentEntry duplicate check prevents double posting', function () {
    $payment = Payment::create([
        'bill_id' => $this->bill->id,
        'payment_date' => now(),
        'amount' => 2000,
        'payment_method' => 'cash',
        'received_by' => $this->user->id,
    ]);

    $first = AccountingService::postPaymentEntry($payment);
    $second = AccountingService::postPaymentEntry($payment);

    // Should return the same entry, not create a new one
    expect($second->id)->toBe($first->id);

    // Only 1 original payment entry should exist
    $count = JournalEntry::where('reference_type', 'Payment')
        ->where('reference_id', $payment->id)
        ->where('entry_type', 'original')
        ->count();
    expect($count)->toBe(1);
});
