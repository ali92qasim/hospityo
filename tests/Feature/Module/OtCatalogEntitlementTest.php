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

function otCatalogTenant(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function otCatalogUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'OT Catalog User',
        'email' => 'ot-catalog-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo($permissions);

    return $user;
}

it('hides the ot group when the module is on but the user has no ot item permissions', function () {
    $labels = collect(app(\App\Services\SidebarService::class)->build(
        otCatalogUser([]),
        otCatalogTenant(['ot', ...\App\Models\ModuleRegistry::OT_CHILD_SLUGS])
    ))->pluck('label');

    expect($labels)->not->toContain('Operation Theatre');
});

it('shows ot inventory for nurse manage ot consumables without view surgeries', function () {
    $group = collect(app(\App\Services\SidebarService::class)->build(
        otCatalogUser(['manage ot consumables']),
        otCatalogTenant(['ot', 'ot.consumables'])
    ))->firstWhere('id', 'ot');

    expect($group)->not->toBeNull()
        ->and(collect($group['items'])->pluck('label')->all())->toBe(['OT Inventory']);
});

it('shows theatres without showing inventory when user only has view surgeries', function () {
    $group = collect(app(\App\Services\SidebarService::class)->build(
        otCatalogUser(['view surgeries']),
        otCatalogTenant(['ot', ...\App\Models\ModuleRegistry::OT_CHILD_SLUGS])
    ))->firstWhere('id', 'ot');

    expect(collect($group['items'])->pluck('label')->all())->toBe(['Theatres', 'Surgeries', 'PAC Requests']);
});

it('forbids consumables index without manage ot consumables even with view surgeries', function () {
    otCatalogTenant(['ot', 'ot.consumables']);
    $this->actingAs(otCatalogUser(['view surgeries']));

    $this->get(route('ot.consumables.index'))->assertForbidden();
});

it('allows consumables index with manage ot consumables and the child slug', function () {
    otCatalogTenant(['ot', 'ot.consumables']);
    $this->actingAs(otCatalogUser(['manage ot consumables']));

    $this->get(route('ot.consumables.index'))->assertOk();
});

it('allows sterilization index with manage sterilization without view surgeries', function () {
    otCatalogTenant(['ot', 'ot.sterilization']);
    $this->actingAs(otCatalogUser(['manage sterilization']));

    $this->get(route('ot.sterilization.index'))->assertOk();
});

it('still allows pac index with view surgeries before the pac route split', function () {
    otCatalogTenant(['ot', 'ot.pac']);
    $this->actingAs(otCatalogUser(['view surgeries']));

    $this->get(route('ot.pac.index'))->assertOk();
});
