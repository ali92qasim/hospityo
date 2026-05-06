<?php

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SubLedgerEntry;
use App\Models\User;
beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
});

it('auto-generates entry number on creation', function () {
    $entry = JournalEntry::create([
        'entry_date' => now(),
        'reference_type' => 'Bill',
        'reference_id' => 1,
        'description' => 'Test journal entry',
        'created_by' => $this->user->id,
    ]);

    expect($entry->entry_number)->toStartWith('JE-')
        ->and($entry->entry_number)->toMatch('/^JE-\d{6}-\d{5}$/');
});

it('can create journal entry with lines', function () {
    $cash = Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
    $revenue = Account::create(['code' => '4100', 'name' => 'OPD Revenue', 'type' => 'revenue']);

    $entry = JournalEntry::create([
        'entry_date' => '2026-04-15',
        'reference_type' => 'Bill',
        'reference_id' => 1,
        'description' => 'OPD bill payment',
        'created_by' => $this->user->id,
        'is_auto' => true,
    ]);

    $entry->lines()->create([
        'account_id' => $cash->id,
        'debit' => 5000,
        'credit' => 0,
        'narration' => 'Cash received',
    ]);

    $entry->lines()->create([
        'account_id' => $revenue->id,
        'debit' => 0,
        'credit' => 5000,
        'narration' => 'OPD revenue',
    ]);

    expect($entry->lines)->toHaveCount(2)
        ->and($entry->is_auto)->toBeTrue();
});

it('validates balanced entry (debits = credits)', function () {
    $cash = Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
    $revenue = Account::create(['code' => '4100', 'name' => 'Revenue', 'type' => 'revenue']);

    $entry = JournalEntry::create([
        'entry_date' => now(),
        'description' => 'Balanced entry',
        'created_by' => $this->user->id,
    ]);

    $entry->lines()->create(['account_id' => $cash->id, 'debit' => 1000, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $revenue->id, 'debit' => 0, 'credit' => 1000]);

    $entry->load('lines');
    expect($entry->isBalanced())->toBeTrue();
});

it('detects unbalanced entry', function () {
    $cash = Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
    $revenue = Account::create(['code' => '4100', 'name' => 'Revenue', 'type' => 'revenue']);

    $entry = JournalEntry::create([
        'entry_date' => now(),
        'description' => 'Unbalanced entry',
        'created_by' => $this->user->id,
    ]);

    $entry->lines()->create(['account_id' => $cash->id, 'debit' => 1000, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $revenue->id, 'debit' => 0, 'credit' => 500]);

    $entry->load('lines');
    expect($entry->isBalanced())->toBeFalse();
});

it('can create sub-ledger entries', function () {
    $entry = JournalEntry::create([
        'entry_date' => now(),
        'description' => 'Patient bill',
        'created_by' => $this->user->id,
    ]);

    $entry->subLedgerEntries()->create([
        'ledger_type' => 'patient',
        'ledger_id' => 1,
        'debit' => 5000,
        'credit' => 0,
        'narration' => 'Invoice for patient',
    ]);

    expect($entry->subLedgerEntries)->toHaveCount(1)
        ->and($entry->subLedgerEntries->first()->ledger_type)->toBe('patient');
});
