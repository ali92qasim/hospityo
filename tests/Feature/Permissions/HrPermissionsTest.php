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

function bindHrTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => $module === 'hr');

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function hrPermissionUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'HR Permission User',
        'email' => 'hr-perm-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

it('blocks payroll when user only has view employees', function () {
    bindHrTenant();
    $this->actingAs(hrPermissionUser(['view employees']));

    $this->get(route('hr.payroll.index'))
        ->assertForbidden();
});

it('allows employee index with view employees', function () {
    bindHrTenant();
    $this->actingAs(hrPermissionUser(['view employees']));

    $this->get(route('hr.employees.index'))
        ->assertOk();
});
