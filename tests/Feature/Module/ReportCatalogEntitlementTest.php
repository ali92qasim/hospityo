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

function reportCatalogTenant(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function reportCatalogUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Report Catalog User',
        'email' => 'report-catalog-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

it('forbids a report route when the child slug is missing from the plan', function () {
    reportCatalogTenant(['reports']);
    $this->actingAs(reportCatalogUser(['view reports', 'view reports.daily-cash-register']));

    $this->get(route('reports.daily-cash-register'))->assertForbidden();
});

it('forbids a report route when the child spatia permission is missing', function () {
    reportCatalogTenant(['reports', 'reports.daily-cash-register']);
    $this->actingAs(reportCatalogUser(['view reports']));

    $this->get(route('reports.daily-cash-register'))->assertForbidden();
});

it('allows a report route when plan child slug and child permission are present', function () {
    reportCatalogTenant(['reports', 'reports.daily-cash-register']);
    $this->actingAs(reportCatalogUser(['view reports', 'view reports.daily-cash-register']));

    $this->get(route('reports.daily-cash-register'))->assertOk();
});
