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

function bindDoctorShareTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => $module === 'doctor-share');

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function doctorSharePermissionUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Doctor Share Permission User',
        'email' => 'doctor-share-perm-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

it('blocks share reports when user only has view share rules', function () {
    bindDoctorShareTenant();
    $this->actingAs(doctorSharePermissionUser(['view share rules']));

    $this->get(route('doctor-share.reports.index'))
        ->assertForbidden();
});

it('allows share rules index with view share rules', function () {
    bindDoctorShareTenant();
    $this->actingAs(doctorSharePermissionUser(['view share rules']));

    $this->get(route('doctor-share.rules.index'))
        ->assertOk();
});
