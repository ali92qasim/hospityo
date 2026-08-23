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

function bindAccountingTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => $module === 'accounting');

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function accountingPermissionUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Accounting Permission User',
        'email' => 'accounting-perm-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

it('blocks journal entries when user only has view chart of accounts', function () {
    bindAccountingTenant();
    $this->actingAs(accountingPermissionUser(['view chart of accounts']));

    $this->get(route('accounting.journal-entries'))
        ->assertForbidden();
});

it('allows chart of accounts with view chart of accounts', function () {
    bindAccountingTenant();
    $this->actingAs(accountingPermissionUser(['view chart of accounts']));

    $this->get(route('accounting.chart-of-accounts'))
        ->assertOk();
});
