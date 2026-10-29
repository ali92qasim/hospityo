<?php

use App\Models\Account;
use App\Models\AccountingPayment;
use App\Models\Bill;
use App\Models\JournalEntry;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Services\AccountingService;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'apsvc-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Account::create(['code' => '1100', 'name' => 'Cash in Hand', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '1110', 'name' => 'Bank Account', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '2100', 'name' => 'Tax Payable', 'type' => 'liability', 'is_system' => true]);
    Account::create(['code' => '2300', 'name' => 'Advance from Patients', 'type' => 'liability', 'is_system' => true]);
    Account::create(['code' => '4100', 'name' => 'OPD Revenue', 'type' => 'revenue', 'is_system' => true]);
    Account::create(['code' => '5200', 'name' => 'Discounts Given', 'type' => 'expense', 'is_system' => true]);

    $this->patient = Patient::create([
        'name' => 'AP Sync Patient',
        'gender' => 'male',
        'age' => 30,
        'phone' => '03003333333',
    ]);

    $this->bill = Bill::create([
        'patient_id' => $this->patient->id,
        'bill_number' => 'BILL-AP-001',
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

    AccountingService::postBillEntry($this->bill);
});

it('posts a bill payment and creates an accounting payment row', function () {
    $payment = Payment::create([
        'bill_id' => $this->bill->id,
        'payment_date' => now(),
        'amount' => 5000,
        'payment_method' => 'cash',
        'received_by' => $this->user->id,
    ]);
    $this->bill->update(['paid_amount' => 5000, 'due_amount' => 5000, 'status' => 'partial']);
    AccountingService::postPaymentEntry($payment);

    $row = AccountingPayment::where('payment_type', 'income')->where('payment_method', 'cash')->sole();
    expect((float) $row->amount)->toBe(5000.0);
});

it('heals missing accounting_payments when postPaymentEntry finds an existing JE', function () {
    $payment = Payment::create([
        'bill_id' => $this->bill->id,
        'payment_date' => now(),
        'amount' => 3000,
        'payment_method' => 'cash',
        'received_by' => $this->user->id,
    ]);

    $cash = Account::where('code', '1100')->firstOrFail();
    $receivable = Account::where('code', '1200')->firstOrFail();

    $entry = JournalEntry::create([
        'entry_date' => $payment->payment_date,
        'reference_type' => 'Payment',
        'reference_id' => $payment->id,
        'description' => 'Pre-feature payment JE without accounting_payments',
        'created_by' => $this->user->id,
        'is_auto' => true,
        'entry_type' => 'original',
    ]);
    $entry->lines()->create([
        'account_id' => $cash->id,
        'debit' => 3000,
        'credit' => 0,
        'narration' => 'Payment via cash',
    ]);
    $entry->lines()->create([
        'account_id' => $receivable->id,
        'debit' => 0,
        'credit' => 3000,
        'narration' => 'Payment against bill',
    ]);

    expect(AccountingPayment::where('journal_entry_id', $entry->id)->count())->toBe(0);

    AccountingService::postPaymentEntry($payment);

    $row = AccountingPayment::where('journal_entry_id', $entry->id)->sole();
    expect($row->payment_type)->toBe('income')
        ->and($row->payment_method)->toBe('cash')
        ->and((float) $row->amount)->toBe(3000.0);
});

it('clears original and syncs reversal when reversing a cash payment entry', function () {
    $payment = Payment::create([
        'bill_id' => $this->bill->id,
        'payment_date' => now(),
        'amount' => 4000,
        'payment_method' => 'cash',
        'received_by' => $this->user->id,
    ]);
    AccountingService::postPaymentEntry($payment);

    $original = JournalEntry::where('reference_type', 'Payment')
        ->where('reference_id', $payment->id)
        ->where('entry_type', 'original')
        ->with(['lines', 'subLedgerEntries'])
        ->firstOrFail();

    $originalId = $original->id;
    $reversal = AccountingService::reverseEntry($original, 'test reverse');
    expect($reversal)->not->toBeNull();

    expect(AccountingPayment::where('journal_entry_id', $originalId)->count())->toBe(0);

    $revRow = AccountingPayment::where('journal_entry_id', $reversal->id)->sole();
    expect($revRow->payment_type)->toBe('expense')
        ->and($revRow->payment_method)->toBe('cash')
        ->and((float) $revRow->amount)->toBe(4000.0);
});
