<?php

use App\Models\Account;
use App\Models\AccountingPayment;
use App\Models\JournalEntry;
use App\Models\User;

function backfillPaymentsUser(): User
{
    return User::create([
        'name' => 'Backfill Payments User',
        'email' => 'backfill-payments-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
}

it('backfills missing accounting payments from existing cash lines', function () {
    $user = backfillPaymentsUser();
    $cash = Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
    $ar = Account::create(['code' => '1200', 'name' => 'AR', 'type' => 'asset']);

    $entry = JournalEntry::create([
        'entry_date' => '2026-10-10',
        'description' => 'Historical cash receipt',
        'created_by' => $user->id,
        'entry_type' => 'original',
    ]);
    $cashLine = $entry->lines()->create(['account_id' => $cash->id, 'debit' => 150.25, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $ar->id, 'debit' => 0, 'credit' => 150.25]);

    expect(AccountingPayment::count())->toBe(0);

    $this->artisan('accounting:backfill-payments')->assertSuccessful();

    $row = AccountingPayment::where('journal_entry_line_id', $cashLine->id)->sole();
    expect($row->payment_type)->toBe('income')
        ->and($row->payment_method)->toBe('cash')
        ->and((float) $row->amount)->toBe(150.25)
        ->and($row->payment_date->format('Y-m-d'))->toBe('2026-10-10')
        ->and($row->journal_entry_id)->toBe($entry->id)
        ->and($row->description)->toBe('Historical cash receipt');
});

it('is idempotent and dry-run writes nothing', function () {
    $user = backfillPaymentsUser();
    $bank = Account::create(['code' => '1110', 'name' => 'Bank', 'type' => 'asset']);
    $expense = Account::create(['code' => '5100', 'name' => 'Expense', 'type' => 'expense']);

    $entry = JournalEntry::create([
        'entry_date' => '2026-10-11',
        'description' => 'Historical bank payout',
        'created_by' => $user->id,
        'entry_type' => 'original',
    ]);
    $entry->lines()->create(['account_id' => $expense->id, 'debit' => 40, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $bank->id, 'debit' => 0, 'credit' => 40]);

    $this->artisan('accounting:backfill-payments')->assertSuccessful();
    expect(AccountingPayment::count())->toBe(1);

    $this->artisan('accounting:backfill-payments', ['--dry-run' => true])->assertSuccessful();
    expect(AccountingPayment::count())->toBe(1);

    $this->artisan('accounting:backfill-payments')->assertSuccessful();
    expect(AccountingPayment::count())->toBe(1);

    $row = AccountingPayment::sole();
    expect($row->payment_type)->toBe('expense')
        ->and($row->payment_method)->toBe('bank')
        ->and((float) $row->amount)->toBe(40.0);
});
