<?php

use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
});

function accountingCatalogTenant(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function accountingCatalogUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Accounting Catalog User',
        'email' => 'accounting-catalog-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

it('hides statement sidebar items when the child slug is missing', function () {
    $tenant = accountingCatalogTenant(['accounting']);
    $user = accountingCatalogUser(['view accounting', 'view profit and loss', 'view chart of accounts']);
    $group = collect(app(\App\Services\SidebarService::class)->build($user, $tenant))->firstWhere('id', 'accounting');

    expect($group)->not->toBeNull()
        ->and(collect($group['items'])->pluck('label')->all())->toBe(['Chart of Accounts', 'Journal Entries']);
});

it('shows employee ledger when the child slug and permission are present', function () {
    $tenant = accountingCatalogTenant(['accounting', 'accounting.employee-ledger']);
    $user = accountingCatalogUser(['view employee ledgers']);
    $group = collect(app(\App\Services\SidebarService::class)->build($user, $tenant))->firstWhere('id', 'accounting');

    expect($group)->not->toBeNull()
        ->and(collect($group['items'])->pluck('label')->all())->toBe(['Employee Ledger']);
});

it('shows profit and loss for the view accounting alias when the child slug is present', function () {
    $tenant = accountingCatalogTenant(['accounting', 'accounting.profit-loss']);
    $user = accountingCatalogUser(['view accounting']);
    $group = collect(app(\App\Services\SidebarService::class)->build($user, $tenant))->firstWhere('id', 'accounting');

    expect(collect($group['items'])->pluck('label')->all())->toBe(['Chart of Accounts', 'Journal Entries', 'Profit & Loss']);
});
