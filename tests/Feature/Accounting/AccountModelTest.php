<?php

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
});

it('can create an account', function () {
    $account = Account::create([
        'code' => '1100',
        'name' => 'Cash in Hand',
        'type' => 'asset',
        'is_system' => true,
        'is_active' => true,
    ]);

    expect($account)->toBeInstanceOf(Account::class)
        ->and($account->code)->toBe('1100')
        ->and($account->name)->toBe('Cash in Hand')
        ->and($account->type)->toBe('asset')
        ->and($account->is_system)->toBeTrue();
});

it('can create parent-child account hierarchy', function () {
    $parent = Account::create([
        'code' => '1000',
        'name' => 'Assets',
        'type' => 'asset',
    ]);

    $child = Account::create([
        'code' => '1100',
        'name' => 'Cash in Hand',
        'type' => 'asset',
        'parent_id' => $parent->id,
    ]);

    expect($child->parent->id)->toBe($parent->id)
        ->and($parent->children)->toHaveCount(1)
        ->and($parent->children->first()->code)->toBe('1100');
});

it('calculates balance correctly for asset accounts (debit - credit)', function () {
    $account = Account::create([
        'code' => '1100',
        'name' => 'Cash in Hand',
        'type' => 'asset',
    ]);

    $entry = JournalEntry::create([
        'entry_date' => now(),
        'reference_type' => 'Test',
        'reference_id' => 1,
        'description' => 'Test entry',
        'created_by' => $this->user->id,
    ]);

    JournalEntryLine::create([
        'journal_entry_id' => $entry->id,
        'account_id' => $account->id,
        'debit' => 5000,
        'credit' => 0,
    ]);

    JournalEntryLine::create([
        'journal_entry_id' => $entry->id,
        'account_id' => $account->id,
        'debit' => 0,
        'credit' => 2000,
    ]);

    expect($account->getBalance())->toBe(3000.0);
});

it('calculates balance correctly for revenue accounts (credit - debit)', function () {
    $account = Account::create([
        'code' => '4100',
        'name' => 'OPD Revenue',
        'type' => 'revenue',
    ]);

    $entry = JournalEntry::create([
        'entry_date' => now(),
        'reference_type' => 'Test',
        'reference_id' => 1,
        'description' => 'Test entry',
        'created_by' => $this->user->id,
    ]);

    JournalEntryLine::create([
        'journal_entry_id' => $entry->id,
        'account_id' => $account->id,
        'debit' => 0,
        'credit' => 10000,
    ]);

    expect($account->getBalance())->toBe(10000.0);
});

it('filters accounts by type scope', function () {
    Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
    Account::create(['code' => '4100', 'name' => 'Revenue', 'type' => 'revenue']);
    Account::create(['code' => '5100', 'name' => 'Expense', 'type' => 'expense']);

    expect(Account::ofType('asset')->count())->toBe(1)
        ->and(Account::ofType('revenue')->count())->toBe(1);
});

it('filters active accounts', function () {
    // Clear any existing accounts to isolate the test
    Account::query()->delete();

    Account::create(['code' => '1100', 'name' => 'Active', 'type' => 'asset', 'is_active' => true]);
    Account::create(['code' => '1200', 'name' => 'Inactive', 'type' => 'asset', 'is_active' => false]);

    expect(Account::active()->count())->toBe(1);
});
