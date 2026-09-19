<?php

use App\Models\SuperAdmin;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'app.version' => '1.2.3',
    ]);
    $this->app['db']->purge('landlord');
    $this->artisan('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
});

function versionFooterTenant(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));
    $tenant->shouldReceive('onTrial')->andReturn(false);

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function versionFooterUser(): User
{
    return User::create([
        'name' => 'Footer User',
        'email' => 'footer-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
}

it('shows a version link to whats-new in the tenant admin layout', function () {
    versionFooterTenant([]);
    $this->actingAs(versionFooterUser())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('v1.2.3')
        ->assertSee(route('whats-new.index'), false);
});

it('shows a version link to whats-new in the super-admin layout', function () {
    $superAdmin = SuperAdmin::create([
        'name' => 'Super Admin',
        'email' => 'super-footer-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($superAdmin, 'super_admin')
        ->get(route('super-admin.dashboard'))
        ->assertOk()
        ->assertSee('v1.2.3')
        ->assertSee(route('super-admin.whats-new.index'), false);
});
