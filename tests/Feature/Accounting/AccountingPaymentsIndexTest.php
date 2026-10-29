<?php

use App\Models\Account;
use App\Models\AccountingPayment;
use App\Models\JournalEntry;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SidebarService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutVite();
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
});

// Renamed copies of AccountingPermissionsTest helpers (Pest globals collide across files).
function paymentsIndexBindTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => $module === 'accounting');

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function paymentsIndexUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Payments Index User',
        'email' => 'payments-index-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function createAccountingPaymentForIndex(array $attrs): AccountingPayment
{
    $user = paymentsIndexUser(['view accounting payments']);

    $cash = Account::firstOrCreate(
        ['code' => '1100'],
        ['name' => 'Cash', 'type' => 'asset']
    );

    $entry = JournalEntry::create([
        'entry_date' => $attrs['payment_date'] ?? '2026-10-01',
        'description' => $attrs['description'] ?? 'Index test',
        'created_by' => $user->id,
        'entry_type' => 'original',
    ]);

    $line = $entry->lines()->create([
        'account_id' => $cash->id,
        'debit' => ($attrs['payment_type'] ?? 'income') === 'expense' ? 0 : ($attrs['amount'] ?? 100),
        'credit' => ($attrs['payment_type'] ?? 'income') === 'expense' ? ($attrs['amount'] ?? 100) : 0,
    ]);

    return AccountingPayment::create([
        'amount' => $attrs['amount'] ?? 100,
        'payment_date' => $attrs['payment_date'] ?? '2026-10-01',
        'payment_type' => $attrs['payment_type'] ?? 'income',
        'payment_method' => $attrs['payment_method'] ?? 'cash',
        'journal_entry_id' => $entry->id,
        'journal_entry_line_id' => $line->id,
        'description' => $attrs['description'] ?? 'Index test',
    ]);
}

it('forbids payments index without permission', function () {
    paymentsIndexBindTenant();
    $this->actingAs(paymentsIndexUser(['view chart of accounts']));

    $this->get(route('accounting.payments'))->assertForbidden();
});

it('allows payments index with view accounting payments', function () {
    paymentsIndexBindTenant();
    $this->actingAs(paymentsIndexUser(['view accounting payments']));

    $this->get(route('accounting.payments'))->assertOk();
});

it('allows payments index with deprecated view accounting', function () {
    paymentsIndexBindTenant();
    $this->actingAs(paymentsIndexUser(['view accounting']));

    $this->get(route('accounting.payments'))->assertOk();
});

it('shows payments sidebar item when permitted', function () {
    $tenant = paymentsIndexBindTenant();
    $user = paymentsIndexUser(['view accounting payments']);

    $group = collect(app(SidebarService::class)->build($user, $tenant))->firstWhere('id', 'accounting');
    $labels = collect($group['items'] ?? [])->pluck('label')->all();

    expect($labels)->toContain('Payments');
});

it('hides payments sidebar item without permission', function () {
    $tenant = paymentsIndexBindTenant();
    $user = paymentsIndexUser(['view chart of accounts']);

    $group = collect(app(SidebarService::class)->build($user, $tenant))->firstWhere('id', 'accounting');
    $labels = collect($group['items'] ?? [])->pluck('label')->all();

    expect($labels)->not->toContain('Payments');
});

it('lists payments with income and expense badges', function () {
    paymentsIndexBindTenant();
    $this->actingAs(paymentsIndexUser(['view accounting payments']));

    createAccountingPaymentForIndex([
        'amount' => 150.50,
        'payment_date' => '2026-10-15',
        'payment_type' => 'income',
        'payment_method' => 'cash',
    ]);
    createAccountingPaymentForIndex([
        'amount' => 75.25,
        'payment_date' => '2026-10-10',
        'payment_type' => 'expense',
        'payment_method' => 'bank',
    ]);

    $this->get(route('accounting.payments'))
        ->assertOk()
        ->assertSee('Income')
        ->assertSee('Expense')
        ->assertSee('Cash')
        ->assertSee('Bank')
        ->assertSee('bg-green-100 text-green-800', false)
        ->assertSee('bg-red-100 text-red-800', false)
        ->assertSee('fa-arrow-down', false)
        ->assertSee('fa-arrow-up', false);
});

it('filters payments by payment_type', function () {
    paymentsIndexBindTenant();
    $this->actingAs(paymentsIndexUser(['view accounting payments']));

    createAccountingPaymentForIndex([
        'amount' => 200,
        'payment_date' => '2026-10-20',
        'payment_type' => 'income',
        'payment_method' => 'cash',
    ]);
    createAccountingPaymentForIndex([
        'amount' => 50,
        'payment_date' => '2026-10-18',
        'payment_type' => 'expense',
        'payment_method' => 'cash',
    ]);

    $this->get(route('accounting.payments', ['payment_type' => 'income']))
        ->assertOk()
        ->assertSee(format_currency(200))
        ->assertDontSee(format_currency(50));
});

it('filters payments by payment_method', function () {
    paymentsIndexBindTenant();
    $this->actingAs(paymentsIndexUser(['view accounting payments']));

    createAccountingPaymentForIndex([
        'amount' => 200,
        'payment_date' => '2026-10-20',
        'payment_type' => 'income',
        'payment_method' => 'cash',
    ]);
    createAccountingPaymentForIndex([
        'amount' => 50,
        'payment_date' => '2026-10-18',
        'payment_type' => 'income',
        'payment_method' => 'bank',
    ]);

    $this->get(route('accounting.payments', ['payment_method' => 'bank']))
        ->assertOk()
        ->assertSee(format_currency(50))
        ->assertDontSee(format_currency(200));
});

it('filters by payment type and method', function () {
    paymentsIndexBindTenant();
    $this->actingAs(paymentsIndexUser(['view accounting payments']));

    createAccountingPaymentForIndex([
        'amount' => 333.33,
        'payment_date' => '2026-10-22',
        'payment_type' => 'income',
        'payment_method' => 'cash',
    ]);
    createAccountingPaymentForIndex([
        'amount' => 444.44,
        'payment_date' => '2026-10-21',
        'payment_type' => 'expense',
        'payment_method' => 'bank',
    ]);

    $this->get(route('accounting.payments', [
        'payment_type' => 'income',
        'payment_method' => 'cash',
    ]))
        ->assertOk()
        ->assertSee(format_currency(333.33))
        ->assertDontSee(format_currency(444.44));
});

it('does not N+1 when listing many accounting payments', function () {
    paymentsIndexBindTenant();
    $this->actingAs(paymentsIndexUser(['view accounting payments']));

    foreach (range(1, 5) as $i) {
        createAccountingPaymentForIndex([
            'amount' => 100 + $i,
            'payment_date' => '2026-10-0'.$i,
            'payment_type' => $i % 2 === 0 ? 'expense' : 'income',
            'payment_method' => $i % 2 === 0 ? 'bank' : 'cash',
            'description' => "N+1 payment {$i}",
        ]);
    }

    DB::connection('tenant')->flushQueryLog();
    DB::connection('tenant')->enableQueryLog();

    $this->get(route('accounting.payments'))->assertOk();

    $queries = DB::connection('tenant')->getQueryLog();
    $relationHits = collect($queries)->filter(function ($q) {
        $sql = strtolower($q['query']);

        return str_contains($sql, 'journal_entry_lines')
            || str_contains($sql, 'from `accounts`')
            || str_contains($sql, 'from "accounts"');
    });

    expect($relationHits)->toHaveCount(0);
});
