<?php

use App\Models\Account;
use App\Models\Bill;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingService;
use App\Exceptions\ClosedPeriodException;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'fylock@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($this->user);

    Account::create(['code' => '1100', 'name' => 'Cash in Hand', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '2100', 'name' => 'Tax Payable', 'type' => 'liability', 'is_system' => true]);
    Account::create(['code' => '4100', 'name' => 'OPD Revenue', 'type' => 'revenue', 'is_system' => true]);
    Account::create(['code' => '5200', 'name' => 'Discounts Given', 'type' => 'expense', 'is_system' => true]);

    $this->patient = Patient::create([
        'name' => 'FY Lock Patient',
        'gender' => 'male',
        'age' => 35,
        'phone' => '03003333333',
    ]);
});

it('blocks journal entry creation in a closed fiscal period', function () {
    FiscalYear::create([
        'name' => 'FY 2025-26',
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
        'is_active' => false,
        'is_closed' => true,
        'closed_at' => now(),
        'closed_by' => $this->user->id,
    ]);

    expect(fn() => JournalEntry::create([
        'entry_date' => '2026-03-15',
        'reference_type' => 'Bill',
        'reference_id' => 1,
        'description' => 'Should be blocked',
        'created_by' => $this->user->id,
        'is_auto' => false,
        'entry_type' => 'original',
    ]))->toThrow(ClosedPeriodException::class);
});

it('allows journal entry creation in an open period', function () {
    FiscalYear::create([
        'name' => 'FY 2025-26',
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
        'is_active' => false,
        'is_closed' => true,
        'closed_at' => now(),
        'closed_by' => $this->user->id,
    ]);

    // Date outside the closed period (July 2026 is FY 2026-27)
    $entry = JournalEntry::create([
        'entry_date' => '2026-07-15',
        'reference_type' => 'Bill',
        'reference_id' => 1,
        'description' => 'Should be allowed',
        'created_by' => $this->user->id,
        'is_auto' => false,
        'entry_type' => 'original',
    ]);

    expect($entry)->not->toBeNull()
        ->and($entry->id)->toBeGreaterThan(0);
});

it('allows system reversal in a closed period when matching entry exists', function () {
    // Create an entry in the period while it's open
    $original = JournalEntry::create([
        'entry_date' => '2026-03-15',
        'reference_type' => 'Bill',
        'reference_id' => 99,
        'description' => 'Original invoice',
        'created_by' => $this->user->id,
        'is_auto' => true,
        'entry_type' => 'original',
    ]);

    // Now close the period
    FiscalYear::create([
        'name' => 'FY 2025-26',
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
        'is_active' => false,
        'is_closed' => true,
        'closed_at' => now(),
        'closed_by' => $this->user->id,
    ]);

    // Reversal should be allowed (links to specific original via reversed_entry_id, same date)
    $reversal = JournalEntry::create([
        'entry_date' => '2026-03-15',
        'reference_type' => 'Bill',
        'reference_id' => 99,
        'description' => 'REVERSAL — Original invoice (test)',
        'created_by' => $this->user->id,
        'is_auto' => true,
        'entry_type' => 'reversal',
        'reversed_entry_id' => $original->id,
    ]);

    expect($reversal)->not->toBeNull()
        ->and($reversal->entry_type)->toBe('reversal')
        ->and($reversal->reversed_entry_id)->toBe($original->id);
});

it('blocks manual reversal in a closed period (not auto-generated)', function () {
    $original = JournalEntry::create([
        'entry_date' => '2026-03-15',
        'reference_type' => 'Bill',
        'reference_id' => 99,
        'description' => 'Original invoice',
        'created_by' => $this->user->id,
        'is_auto' => true,
        'entry_type' => 'original',
    ]);

    FiscalYear::create([
        'name' => 'FY 2025-26',
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
        'is_active' => false,
        'is_closed' => true,
        'closed_at' => now(),
        'closed_by' => $this->user->id,
    ]);

    // Reversal without reversed_entry_id should be blocked (no link to verify)
    expect(fn() => JournalEntry::create([
        'entry_date' => '2026-03-15',
        'reference_type' => 'Bill',
        'reference_id' => 99,
        'description' => 'Manual reversal attempt',
        'created_by' => $this->user->id,
        'is_auto' => false,
        'entry_type' => 'reversal',
    ]))->toThrow(ClosedPeriodException::class);
});

it('blocks reversal with non-matching date in closed period', function () {
    $original = JournalEntry::create([
        'entry_date' => '2026-03-15',
        'reference_type' => 'Bill',
        'reference_id' => 99,
        'description' => 'Original invoice',
        'created_by' => $this->user->id,
        'is_auto' => true,
        'entry_type' => 'original',
    ]);

    FiscalYear::create([
        'name' => 'FY 2025-26',
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
        'is_active' => false,
        'is_closed' => true,
        'closed_at' => now(),
        'closed_by' => $this->user->id,
    ]);

    // Reversal with reversed_entry_id but DIFFERENT date than original — should be blocked
    expect(fn() => JournalEntry::create([
        'entry_date' => '2026-04-01', // different from original's 2026-03-15
        'reference_type' => 'Bill',
        'reference_id' => 99,
        'description' => 'REVERSAL — wrong date',
        'created_by' => $this->user->id,
        'is_auto' => true,
        'entry_type' => 'reversal',
        'reversed_entry_id' => $original->id,
    ]))->toThrow(ClosedPeriodException::class);
});

it('postBillEntry throws ClosedPeriodException for bill dated in closed period', function () {
    FiscalYear::create([
        'name' => 'FY 2025-26',
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
        'is_active' => false,
        'is_closed' => true,
        'closed_at' => now(),
        'closed_by' => $this->user->id,
    ]);

    $bill = Bill::create([
        'patient_id' => $this->patient->id,
        'bill_number' => 'BILL-FY-001',
        'bill_date' => '2026-05-10', // in closed period
        'bill_type' => 'opd',
        'subtotal' => 5000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 5000,
        'paid_amount' => 0,
        'due_amount' => 5000,
        'status' => 'pending',
        'created_by' => $this->user->id,
    ]);

    // postBillEntry uses bill->bill_date which is in the closed period
    $result = AccountingService::postBillEntry($bill);

    // Should return null (the try/catch in postBillEntry catches the exception)
    expect($result)->toBeNull();
});
