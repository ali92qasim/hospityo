<?php

use App\Models\Account;
use App\Models\AccountingPayment;
use App\Models\JournalEntry;
use App\Models\User;

it('persists an accounting payment row', function () {
    $user = User::create([
        'name' => 'AP Model User',
        'email' => 'ap-model-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $cash = Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
    $entry = JournalEntry::create([
        'entry_date' => '2026-10-01',
        'description' => 'Test',
        'created_by' => $user->id,
        'entry_type' => 'original',
    ]);
    $line = $entry->lines()->create([
        'account_id' => $cash->id,
        'debit' => 100,
        'credit' => 0,
    ]);

    $payment = AccountingPayment::create([
        'amount' => 100,
        'payment_date' => '2026-10-01',
        'payment_type' => 'income',
        'payment_method' => 'cash',
        'journal_entry_id' => $entry->id,
        'journal_entry_line_id' => $line->id,
        'description' => 'Test',
    ]);

    expect($payment->fresh()->payment_type)->toBe('income')
        ->and($payment->payment_method)->toBe('cash')
        ->and((float) $payment->amount)->toBe(100.0);
});
