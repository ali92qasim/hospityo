<?php

use App\Models\Account;
use App\Models\Bill;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingService;
beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    // Seed required accounts
    Account::create(['code' => '1100', 'name' => 'Cash in Hand', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '1110', 'name' => 'Bank Account', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '1210', 'name' => 'Insurance Receivable', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '1300', 'name' => 'Inventory', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '1400', 'name' => 'Input Tax', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '2100', 'name' => 'Tax Payable', 'type' => 'liability', 'is_system' => true]);
    Account::create(['code' => '2200', 'name' => 'Accounts Payable', 'type' => 'liability', 'is_system' => true]);
    Account::create(['code' => '4100', 'name' => 'OPD Revenue', 'type' => 'revenue', 'is_system' => true]);
    Account::create(['code' => '4200', 'name' => 'IPD Revenue', 'type' => 'revenue', 'is_system' => true]);
    Account::create(['code' => '4300', 'name' => 'Investigation Revenue', 'type' => 'revenue', 'is_system' => true]);
    Account::create(['code' => '4400', 'name' => 'Pharmacy Revenue', 'type' => 'revenue', 'is_system' => true]);
    Account::create(['code' => '4500', 'name' => 'Emergency Revenue', 'type' => 'revenue', 'is_system' => true]);
    Account::create(['code' => '5200', 'name' => 'Discounts Given', 'type' => 'expense', 'is_system' => true]);
    Account::create(['code' => '5300', 'name' => 'Salaries & Wages', 'type' => 'expense', 'is_system' => true]);

    // Create a test patient
    $this->patient = Patient::create([
        'name' => 'John Doe',
        'gender' => 'male',
        'age' => 35,
        'phone' => '03001234567',
        'emergency_name' => 'Jane Doe',
        'emergency_phone' => '03009876543',
        'emergency_relation' => 'Spouse',
    ]);
});

it('posts bill entry with correct debit/credit', function () {
    $bill = Bill::create([
        'patient_id' => $this->patient->id,
        'bill_number' => 'BILL-001',
        'bill_date' => now(),
        'bill_type' => 'opd',
        'subtotal' => 5000,
        'tax_amount' => 500,
        'discount_amount' => 0,
        'total_amount' => 5500,
        'paid_amount' => 0,
        'due_amount' => 5500,
        'status' => 'pending',
        'created_by' => $this->user->id,
    ]);

    $entry = AccountingService::postBillEntry($bill);

    expect($entry)->not->toBeNull()
        ->and($entry->reference_type)->toBe('Bill')
        ->and($entry->reference_id)->toBe($bill->id)
        ->and($entry->is_auto)->toBeTrue();

    // Check lines: DR Receivable 5500, CR Revenue 5000, CR Tax 500
    $lines = $entry->lines;
    $debitTotal = $lines->sum('debit');
    $creditTotal = $lines->sum('credit');

    expect((float) $debitTotal)->toBe(5500.0)
        ->and((float) $creditTotal)->toBe(5500.0);

    // Check sub-ledger
    expect($entry->subLedgerEntries)->toHaveCount(1)
        ->and($entry->subLedgerEntries->first()->ledger_type)->toBe('patient')
        ->and((float) $entry->subLedgerEntries->first()->debit)->toBe(5500.0);
});

it('posts bill entry with discount', function () {
    $bill = Bill::create([
        'patient_id' => $this->patient->id,
        'bill_number' => 'BILL-002',
        'bill_date' => now(),
        'bill_type' => 'opd',
        'subtotal' => 5000,
        'tax_amount' => 0,
        'discount_amount' => 500,
        'total_amount' => 4500,
        'paid_amount' => 0,
        'due_amount' => 4500,
        'status' => 'pending',
        'created_by' => $this->user->id,
    ]);

    $entry = AccountingService::postBillEntry($bill);

    expect($entry)->not->toBeNull();

    // DR Receivable 4500, DR Discount 500, CR Revenue 5000
    $lines = $entry->lines;
    $totalDebit = $lines->sum('debit');
    $totalCredit = $lines->sum('credit');

    expect((float) $totalDebit)->toBe(5000.0)
        ->and((float) $totalCredit)->toBe(5000.0);
});

it('posts payment entry correctly', function () {
    $bill = Bill::create([
        'patient_id' => $this->patient->id,
        'bill_number' => 'BILL-003',
        'bill_date' => now(),
        'bill_type' => 'opd',
        'subtotal' => 3000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 3000,
        'paid_amount' => 0,
        'due_amount' => 3000,
        'status' => 'pending',
        'created_by' => $this->user->id,
    ]);

    $payment = Payment::create([
        'bill_id' => $bill->id,
        'payment_date' => now(),
        'amount' => 3000,
        'payment_method' => 'cash',
        'received_by' => $this->user->id,
    ]);

    $entry = AccountingService::postPaymentEntry($payment);

    expect($entry)->not->toBeNull()
        ->and($entry->reference_type)->toBe('Payment');

    // DR Cash 3000, CR Receivable 3000
    $lines = $entry->lines;
    expect($lines)->toHaveCount(2)
        ->and((float) $lines->sum('debit'))->toBe(3000.0)
        ->and((float) $lines->sum('credit'))->toBe(3000.0);
});

it('maps bill types to correct revenue accounts', function () {
    // Note: 'investigation' is not in the original ENUM for SQLite tests
    // The ENUM includes: opd, ipd, emergency, lab, pharmacy
    $types = ['opd' => '4100', 'ipd' => '4200', 'pharmacy' => '4400', 'emergency' => '4500'];

    foreach ($types as $billType => $expectedCode) {
        $bill = Bill::create([
            'patient_id' => $this->patient->id,
            'bill_number' => "BILL-{$billType}",
            'bill_date' => now(),
            'bill_type' => $billType,
            'subtotal' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'paid_amount' => 0,
            'due_amount' => 1000,
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        $entry = AccountingService::postBillEntry($bill);
        expect($entry)->not->toBeNull();

        // The credit line should be on the expected revenue account
        $creditLine = $entry->lines->where('credit', '>', 0)->first();
        $account = Account::find($creditLine->account_id);
        expect($account->code)->toBe($expectedCode);
    }
});

it('returns null when required accounts are missing', function () {
    // Delete all accounts
    Account::query()->delete();

    $bill = Bill::create([
        'patient_id' => $this->patient->id,
        'bill_number' => 'BILL-FAIL',
        'bill_date' => now(),
        'bill_type' => 'opd',
        'subtotal' => 1000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 1000,
        'paid_amount' => 0,
        'due_amount' => 1000,
        'status' => 'pending',
        'created_by' => $this->user->id,
    ]);

    $entry = AccountingService::postBillEntry($bill);
    expect($entry)->toBeNull();
});
