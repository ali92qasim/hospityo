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

function hrCatalogTenant(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function hrCatalogUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'HR Catalog User',
        'email' => 'hr-catalog-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo($permissions);

    return $user;
}

it('hides designations when hr is on the plan but hr.employees is missing', function () {
    $tenant = hrCatalogTenant(['hr']);
    $user = hrCatalogUser(['view designations', 'view hr']);
    $group = collect(app(\App\Services\SidebarService::class)->build($user, $tenant))->firstWhere('id', 'hr');

    expect($group)->toBeNull();
});

it('shows designations and employees when hr.employees is entitled, not payroll', function () {
    $tenant = hrCatalogTenant(['hr', 'hr.employees']);
    $user = hrCatalogUser(['view employees', 'view designations']);
    $group = collect(app(\App\Services\SidebarService::class)->build($user, $tenant))->firstWhere('id', 'hr');

    expect($group)->not->toBeNull()
        ->and(collect($group['items'])->pluck('label')->all())->toBe(['Employees', 'Designations']);
});

it('shows all nine hr items for view hr when every child slug is present', function () {
    $tenant = hrCatalogTenant(array_merge(['hr'], \App\Models\ModuleRegistry::HR_CHILD_SLUGS));
    $user = hrCatalogUser(['view hr']);
    $group = collect(app(\App\Services\SidebarService::class)->build($user, $tenant))->firstWhere('id', 'hr');

    expect(collect($group['items'])->pluck('label')->all())->toBe([
        'Employees',
        'Designations',
        'Attendance',
        'Leave Requests',
        'Leave Balances',
        'Payroll',
        'Shifts',
        'Duty Roster',
        'Departments Staff',
    ]);
});

it('forbids payroll when hr.payroll is missing from the plan', function () {
    hrCatalogTenant(['hr']);
    $this->actingAs(hrCatalogUser(['view payroll runs', 'view hr']));

    $this->get(route('hr.payroll.index'))->assertForbidden();
});

it('allows payroll when parent, child slug, and permission are present', function () {
    hrCatalogTenant(['hr', 'hr.payroll']);
    $this->actingAs(hrCatalogUser(['view payroll runs']));

    $this->get(route('hr.payroll.index'))->assertOk();
});

it('allows payroll via view hr when hr.payroll is on the plan', function () {
    hrCatalogTenant(['hr', 'hr.payroll']);
    $this->actingAs(hrCatalogUser(['view hr']));

    $this->get(route('hr.payroll.index'))->assertOk();
});

it('allows employee index on hr.employees without payroll child', function () {
    hrCatalogTenant(['hr', 'hr.employees']);
    $this->actingAs(hrCatalogUser(['view employees']));

    $this->get(route('hr.employees.index'))->assertOk();
});

it('forbids duty roster when hr.scheduling is missing', function () {
    hrCatalogTenant(['hr']);
    $this->actingAs(hrCatalogUser(['view duty roster', 'view hr']));

    $this->get(route('hr.shifts.roster'))->assertForbidden();
});
