<?php

use App\Models\Account;
use App\Models\AccountingPayment;
use App\Models\JournalEntry;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
});

/**
 * Local copies of AccountingPermissionsTest helpers (different names to avoid
 * Pest collisions when that file is also loaded).
 */
function apWriteSyncTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => $module === 'accounting');

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function apWriteSyncUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Accounting Payment Write Sync User',
        'email' => 'ap-write-sync-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

it('records an accounting payment when depositing into cash', function () {
    apWriteSyncTenant();

    $cash = Account::create(['code' => '1100', 'name' => 'Cash in Hand', 'type' => 'asset', 'is_active' => true]);
    $source = Account::create(['code' => '4100', 'name' => 'OPD Revenue', 'type' => 'revenue', 'is_active' => true]);

    $this->actingAs(apWriteSyncUser(['create deposits']));

    $this->post(route('accounting.process-deposit'), [
        'to_account_id' => $cash->id,
        'from_account_id' => $source->id,
        'amount' => 150,
        'date' => '2026-10-10',
        'description' => 'Daily deposit',
    ])->assertRedirect();

    expect(AccountingPayment::query()->count())->toBe(1)
        ->and(AccountingPayment::first()->payment_type)->toBe('income')
        ->and(AccountingPayment::first()->payment_method)->toBe('cash');
});

it('records two accounting payments for a cash to bank transfer', function () {
    apWriteSyncTenant();

    $cash = Account::create(['code' => '1100', 'name' => 'Cash in Hand', 'type' => 'asset', 'is_active' => true]);
    $bank = Account::create(['code' => '1110', 'name' => 'Bank Account', 'type' => 'asset', 'is_active' => true]);

    $this->actingAs(apWriteSyncUser(['create transfers']));

    $this->post(route('accounting.process-transfer'), [
        'from_account_id' => $cash->id,
        'to_account_id' => $bank->id,
        'amount' => 500,
        'date' => '2026-10-11',
        'description' => 'Cash to bank',
    ])->assertRedirect();

    $rows = AccountingPayment::query()->get();
    expect($rows)->toHaveCount(2);
    expect($rows->firstWhere('payment_method', 'bank')->payment_type)->toBe('income');
    expect($rows->firstWhere('payment_method', 'cash')->payment_type)->toBe('expense');
});

it('records an accounting payment when storing a manual JE with a cash debit', function () {
    apWriteSyncTenant();

    $cash = Account::create(['code' => '1100', 'name' => 'Cash in Hand', 'type' => 'asset', 'is_active' => true]);
    $revenue = Account::create(['code' => '4100', 'name' => 'OPD Revenue', 'type' => 'revenue', 'is_active' => true]);

    $this->actingAs(apWriteSyncUser(['create journal entries']));

    $this->post(route('accounting.store-journal-entry'), [
        'entry_date' => '2026-10-12',
        'description' => 'Manual cash receipt',
        'lines' => [
            ['account_id' => $cash->id, 'debit' => 200, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 200],
        ],
    ])->assertRedirect();

    $row = AccountingPayment::query()->sole();
    expect($row->payment_type)->toBe('income')
        ->and($row->payment_method)->toBe('cash')
        ->and((float) $row->amount)->toBe(200.0);
});

it('resyncs accounting payments when updating a manual JE cash line', function () {
    apWriteSyncTenant();

    $cash = Account::create(['code' => '1100', 'name' => 'Cash in Hand', 'type' => 'asset', 'is_active' => true]);
    $bank = Account::create(['code' => '1110', 'name' => 'Bank Account', 'type' => 'asset', 'is_active' => true]);
    $revenue = Account::create(['code' => '4100', 'name' => 'OPD Revenue', 'type' => 'revenue', 'is_active' => true]);

    $this->actingAs(apWriteSyncUser(['create journal entries', 'edit journal entries']));

    $this->post(route('accounting.store-journal-entry'), [
        'entry_date' => '2026-10-13',
        'description' => 'Cash then bank',
        'lines' => [
            ['account_id' => $cash->id, 'debit' => 75, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 75],
        ],
    ])->assertRedirect();

    expect(AccountingPayment::query()->sole()->payment_method)->toBe('cash');

    $entry = JournalEntry::query()->latest('id')->firstOrFail();

    $this->put(route('accounting.update-journal-entry', $entry), [
        'entry_date' => '2026-10-13',
        'description' => 'Cash then bank',
        'lines' => [
            ['account_id' => $bank->id, 'debit' => 90, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 90],
        ],
    ])->assertRedirect();

    $row = AccountingPayment::query()->sole();
    expect($row->payment_method)->toBe('bank')
        ->and((float) $row->amount)->toBe(90.0);
});
