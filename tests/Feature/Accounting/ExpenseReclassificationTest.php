<?php

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    foreach ([
        'view accounting',
        'create transfers',
        'view reports.daily-cash-register',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::create([
        'name' => 'Expense Reclass User',
        'email' => 'expense-reclass-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo([
        'view accounting',
        'create transfers',
        'view reports.daily-cash-register',
    ]);
    $this->actingAs($this->user);

    $this->cash = Account::create(['code' => '1100', 'name' => 'Cash in Hand', 'type' => 'asset', 'is_active' => true]);
    $this->utilities = Account::create(['code' => '5401', 'name' => 'Utilities Expense', 'type' => 'expense', 'is_active' => true]);
    $this->repairs = Account::create(['code' => '5402', 'name' => 'Repairs Expense', 'type' => 'expense', 'is_active' => true]);
});

function postExpenseReclass($test, float $amount = 1500): void
{
    $test->post(route('accounting.process-transfer'), [
        'from_account_id' => $test->utilities->id,
        'to_account_id' => $test->repairs->id,
        'amount' => $amount,
        'date' => now()->toDateString(),
        'description' => 'Move utilities cost to repairs',
    ])->assertRedirect(route('accounting.chart-of-accounts'))
        ->assertSessionHas('success');
}

it('records an expense to expense transfer as a balanced journal entry without touching cash', function () {
    postExpenseReclass($this);

    $entry = JournalEntry::with('lines')->latest('id')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->description)->toBe('Move utilities cost to repairs')
        ->and($entry->isBalanced())->toBeTrue()
        ->and((float) $this->cash->getBalance())->toBe(0.0)
        ->and((float) $this->utilities->getBalance())->toBe(-1500.0)
        ->and((float) $this->repairs->getBalance())->toBe(1500.0);
});

it('shows the expense reclassification on journal entries with both accounts', function () {
    postExpenseReclass($this);

    $this->get(route('accounting.journal-entries'))
        ->assertOk()
        ->assertSee('Move utilities cost to repairs')
        ->assertSee('5401 — Utilities Expense', false)
        ->assertSee('5402 — Repairs Expense', false)
        ->assertSee('Reclassified', false);
});

it('shows the expense reclassification on each account general ledger', function () {
    postExpenseReclass($this);

    $this->get(route('accounting.general-ledger', [
        'account_id' => $this->utilities->id,
        'from' => now()->toDateString(),
        'to' => now()->toDateString(),
    ]))
        ->assertOk()
        ->assertSee('Move utilities cost to repairs')
        ->assertSee('Reclassified', false);

    $this->get(route('accounting.general-ledger', [
        'account_id' => $this->repairs->id,
        'from' => now()->toDateString(),
        'to' => now()->toDateString(),
    ]))
        ->assertOk()
        ->assertSee('Move utilities cost to repairs')
        ->assertSee('Reclassified', false);
});

it('shifts profit and loss expense lines without changing total expenses', function () {
    postExpenseReclass($this);

    $this->get(route('accounting.profit-loss', [
        'from' => now()->toDateString(),
        'to' => now()->toDateString(),
    ]))
        ->assertOk()
        ->assertSee('Utilities Expense')
        ->assertSee('Repairs Expense')
        ->assertSee('general-ledger?account_id='.$this->utilities->id, false)
        ->assertSee('general-ledger?account_id='.$this->repairs->id, false);
});

it('links chart of accounts expenses to their general ledger', function () {
    $this->get(route('accounting.chart-of-accounts'))
        ->assertOk()
        ->assertSee('general-ledger?account_id='.$this->utilities->id, false)
        ->assertSee('general-ledger?account_id='.$this->repairs->id, false);
});

it('does not treat an expense to expense reclassification as a cash register outflow', function () {
    postExpenseReclass($this);

    $this->get(route('reports.daily-cash-register', [
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
    ]))
        ->assertOk()
        ->assertDontSee('Repairs Expense')
        ->assertDontSee('Utilities Expense')
        ->assertDontSee('1,500.00', false);
});

it('still shows a cash-paid expense as a cash register outflow', function () {
    $entry = JournalEntry::create([
        'entry_date' => now()->toDateString(),
        'description' => 'Paid electricity bill',
        'created_by' => $this->user->id,
        'is_auto' => false,
        'entry_type' => 'original',
    ]);
    $entry->lines()->create([
        'account_id' => $this->utilities->id,
        'debit' => 800,
        'credit' => 0,
        'narration' => 'Electricity paid',
    ]);
    $entry->lines()->create([
        'account_id' => $this->cash->id,
        'debit' => 0,
        'credit' => 800,
        'narration' => 'Cash paid',
    ]);

    $this->get(route('reports.daily-cash-register', [
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
    ]))
        ->assertOk()
        ->assertSee('Utilities Expense')
        ->assertSee('Paid electricity bill')
        ->assertSee('800.00', false);
});
