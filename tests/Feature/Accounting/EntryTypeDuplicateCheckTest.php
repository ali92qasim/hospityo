<?php

use App\Models\Account;
use App\Models\Bill;
use App\Models\Patient;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingService;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'entrytype@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($this->user);

    // Seed required accounts
    Account::create(['code' => '1100', 'name' => 'Cash in Hand', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '2100', 'name' => 'Tax Payable', 'type' => 'liability', 'is_system' => true]);
    Account::create(['code' => '2300', 'name' => 'Advance from Patients', 'type' => 'liability', 'is_system' => true]);
    Account::create(['code' => '4100', 'name' => 'OPD Revenue', 'type' => 'revenue', 'is_system' => true]);
    Account::create(['code' => '5200', 'name' => 'Discounts Given', 'type' => 'expense', 'is_system' => true]);

    $this->patient = Patient::create([
        'name' => 'Entry Type Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03001111111',
    ]);

    $this->bill = Bill::create([
        'patient_id' => $this->patient->id,
        'bill_number' => 'BILL-ET-001',
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
});

it('posts a bill entry with entry_type = original', function () {
    $entry = AccountingService::postBillEntry($this->bill);

    expect($entry)->not->toBeNull()
        ->and($entry->entry_type)->toBe('original');
});

it('skips duplicate when calling postBillEntry twice without reversal', function () {
    $first = AccountingService::postBillEntry($this->bill);
    $second = AccountingService::postBillEntry($this->bill);

    // Should return the same existing entry, not create a new one
    expect($second->id)->toBe($first->id);

    // Only 1 original entry should exist
    $count = JournalEntry::where('reference_type', 'Bill')
        ->where('reference_id', $this->bill->id)
        ->where('entry_type', 'original')
        ->count();

    expect($count)->toBe(1);
});

it('allows postBillEntry after reverseAndRepost creates a new original', function () {
    // Post initial entry
    $initial = AccountingService::postBillEntry($this->bill);
    expect($initial)->not->toBeNull()
        ->and($initial->entry_type)->toBe('original');

    // Reverse and repost (simulates bill edit)
    $revised = AccountingService::reverseAndRepostBillEntry($this->bill, 'test_edit');
    expect($revised)->not->toBeNull()
        ->and($revised->entry_type)->toBe('original')
        ->and($revised->id)->not->toBe($initial->id);

    // The initial entry should now be marked as superseded
    expect($initial->fresh()->entry_type)->toBe('superseded');

    // Verify a reversal entry was created
    $reversals = JournalEntry::where('reference_type', 'Bill')
        ->where('reference_id', $this->bill->id)
        ->where('entry_type', 'reversal')
        ->count();
    expect($reversals)->toBe(1);

    // Only 1 active original should remain (the revised one)
    $originals = JournalEntry::where('reference_type', 'Bill')
        ->where('reference_id', $this->bill->id)
        ->where('entry_type', 'original')
        ->count();
    expect($originals)->toBe(1);

    // Calling postBillEntry() directly should be skipped because
    // the revised entry (entry_type=original) already exists
    $duplicate = AccountingService::postBillEntry($this->bill);
    expect($duplicate->id)->toBe($revised->id);
});

it('sets entry_type = reversal on reverseEntry', function () {
    $original = AccountingService::postBillEntry($this->bill);
    $reversal = AccountingService::reverseEntry($original, 'test_reversal');

    expect($reversal)->not->toBeNull()
        ->and($reversal->entry_type)->toBe('reversal')
        ->and($reversal->description)->toContain('REVERSAL');
});

it('sets entry_type = adjustment on overpayment', function () {
    $adjustment = AccountingService::postOverpaymentAdjustment($this->bill, 500.00);

    expect($adjustment)->not->toBeNull()
        ->and($adjustment->entry_type)->toBe('adjustment')
        ->and($adjustment->description)->toContain('Overpayment');
});

it('backfill correctly classifies entries by description prefix', function () {
    // Simulate pre-migration entries (entry_type defaults to 'original')
    $reversal = JournalEntry::create([
        'entry_date' => now(),
        'reference_type' => 'Bill',
        'reference_id' => $this->bill->id,
        'description' => 'REVERSAL — Invoice BILL-ET-001 (test)',
        'created_by' => $this->user->id,
        'is_auto' => true,
        'entry_type' => 'original', // simulating pre-backfill state
    ]);

    $adjustment = JournalEntry::create([
        'entry_date' => now(),
        'reference_type' => 'Bill',
        'reference_id' => $this->bill->id,
        'description' => 'Overpayment adjustment — BILL-ET-001 (patient credit)',
        'created_by' => $this->user->id,
        'is_auto' => true,
        'entry_type' => 'original', // simulating pre-backfill state
    ]);

    $normal = JournalEntry::create([
        'entry_date' => now(),
        'reference_type' => 'Bill',
        'reference_id' => $this->bill->id,
        'description' => 'Invoice BILL-ET-001 — Entry Type Patient',
        'created_by' => $this->user->id,
        'is_auto' => true,
        'entry_type' => 'original',
    ]);

    // Run the backfill logic (same as migration)
    JournalEntry::where('description', 'like', 'REVERSAL%')
        ->where('entry_type', 'original')
        ->update(['entry_type' => 'reversal']);

    JournalEntry::where('description', 'like', 'Overpayment%')
        ->where('entry_type', 'original')
        ->update(['entry_type' => 'adjustment']);

    // Verify classification
    expect($reversal->fresh()->entry_type)->toBe('reversal')
        ->and($adjustment->fresh()->entry_type)->toBe('adjustment')
        ->and($normal->fresh()->entry_type)->toBe('original');
});
