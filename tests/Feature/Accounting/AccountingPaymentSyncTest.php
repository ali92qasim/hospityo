<?php

use App\Models\Account;
use App\Models\AccountingPayment;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingPaymentSync;

function apSyncUser(): User
{
    return User::create([
        'name' => 'Sync User',
        'email' => 'ap-sync-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
}

it('syncs cash debit as income cash', function () {
    $user = apSyncUser();
    $cash = Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
    $ar = Account::create(['code' => '1200', 'name' => 'AR', 'type' => 'asset']);

    $entry = JournalEntry::create([
        'entry_date' => '2026-10-02',
        'description' => 'Payment received',
        'created_by' => $user->id,
        'entry_type' => 'original',
    ]);
    $entry->lines()->create(['account_id' => $cash->id, 'debit' => 250.50, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $ar->id, 'debit' => 0, 'credit' => 250.50]);

    AccountingPaymentSync::syncEntry($entry->fresh());

    $row = AccountingPayment::where('journal_entry_id', $entry->id)->sole();
    expect($row->payment_type)->toBe('income')
        ->and($row->payment_method)->toBe('cash')
        ->and((float) $row->amount)->toBe(250.50)
        ->and($row->payment_date->format('Y-m-d'))->toBe('2026-10-02');
});

it('syncs bank credit as expense bank', function () {
    $user = apSyncUser();
    $bank = Account::create(['code' => '1110', 'name' => 'Bank', 'type' => 'asset']);
    $expense = Account::create(['code' => '5100', 'name' => 'Expense', 'type' => 'expense']);

    $entry = JournalEntry::create([
        'entry_date' => '2026-10-03',
        'description' => 'Bank payout',
        'created_by' => $user->id,
        'entry_type' => 'original',
    ]);
    $entry->lines()->create(['account_id' => $expense->id, 'debit' => 80, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $bank->id, 'debit' => 0, 'credit' => 80]);

    AccountingPaymentSync::syncEntry($entry->fresh());

    $row = AccountingPayment::where('journal_entry_id', $entry->id)->sole();
    expect($row->payment_type)->toBe('expense')
        ->and($row->payment_method)->toBe('bank')
        ->and((float) $row->amount)->toBe(80.0);
});

it('creates both sides for a cash to bank transfer', function () {
    $user = apSyncUser();
    $cash = Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
    $bank = Account::create(['code' => '1110', 'name' => 'Bank', 'type' => 'asset']);

    $entry = JournalEntry::create([
        'entry_date' => '2026-10-04',
        'description' => 'Transfer cash to bank',
        'created_by' => $user->id,
        'entry_type' => 'original',
        'reference_type' => 'Transfer',
    ]);
    $entry->lines()->create(['account_id' => $bank->id, 'debit' => 500, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $cash->id, 'debit' => 0, 'credit' => 500]);

    AccountingPaymentSync::syncEntry($entry->fresh());

    $rows = AccountingPayment::where('journal_entry_id', $entry->id)->get();
    expect($rows)->toHaveCount(2);
    expect($rows->firstWhere('payment_method', 'bank')->payment_type)->toBe('income');
    expect($rows->firstWhere('payment_method', 'cash')->payment_type)->toBe('expense');
});

it('clearEntry removes all rows for a journal entry', function () {
    $user = apSyncUser();
    $cash = Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
    $ar = Account::create(['code' => '1200', 'name' => 'AR', 'type' => 'asset']);

    $entry = JournalEntry::create([
        'entry_date' => '2026-10-05',
        'description' => 'To clear',
        'created_by' => $user->id,
        'entry_type' => 'original',
    ]);
    $entry->lines()->create(['account_id' => $cash->id, 'debit' => 10, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $ar->id, 'debit' => 0, 'credit' => 10]);
    AccountingPaymentSync::syncEntry($entry->fresh());

    AccountingPaymentSync::clearEntry($entry->id);

    expect(AccountingPayment::where('journal_entry_id', $entry->id)->count())->toBe(0);
});

it('resync replaces prior rows for the same entry', function () {
    $user = apSyncUser();
    $cash = Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
    $bank = Account::create(['code' => '1110', 'name' => 'Bank', 'type' => 'asset']);
    $ar = Account::create(['code' => '1200', 'name' => 'AR', 'type' => 'asset']);

    $entry = JournalEntry::create([
        'entry_date' => '2026-10-06',
        'description' => 'Resync',
        'created_by' => $user->id,
        'entry_type' => 'original',
    ]);
    $entry->lines()->create(['account_id' => $cash->id, 'debit' => 10, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $ar->id, 'debit' => 0, 'credit' => 10]);
    AccountingPaymentSync::syncEntry($entry->fresh());

    $entry->lines()->delete();
    $entry->lines()->create(['account_id' => $bank->id, 'debit' => 20, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $ar->id, 'debit' => 0, 'credit' => 20]);
    AccountingPaymentSync::syncEntry($entry->fresh());

    $row = AccountingPayment::where('journal_entry_id', $entry->id)->sole();
    expect($row->payment_method)->toBe('bank')
        ->and((float) $row->amount)->toBe(20.0);
});
