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

function pharmacyCatalogTenant(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function pharmacyCatalogUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Pharmacy Catalog User',
        'email' => 'pharmacy-catalog-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

it('hides pos when pharmacy is on the plan but pharmacy.pos is not', function () {
    $tenant = pharmacyCatalogTenant(['pharmacy']);
    $user = pharmacyCatalogUser(['view pos']);
    $group = collect(app(\App\Services\SidebarService::class)->build($user, $tenant))->firstWhere('id', 'pharmacy');

    expect($group)->toBeNull();
});

it('shows pos when pharmacy.pos is entitled even if catalog is not', function () {
    $tenant = pharmacyCatalogTenant(['pharmacy', 'pharmacy.pos']);
    $user = pharmacyCatalogUser(['view pos']);
    $group = collect(app(\App\Services\SidebarService::class)->build($user, $tenant))->firstWhere('id', 'pharmacy');

    expect($group)->not->toBeNull()
        ->and(collect($group['items'])->pluck('label')->all())->toBe(['POS']);
});

it('forbids pos when pharmacy.pos is missing from the plan', function () {
    pharmacyCatalogTenant(['pharmacy']);
    $this->actingAs(pharmacyCatalogUser(['view pos']));

    $this->get(route('pharmacy.pos.index'))->assertForbidden();
});

it('allows pos when pharmacy.pos is entitled', function () {
    pharmacyCatalogTenant(['pharmacy', 'pharmacy.pos']);
    $this->actingAs(pharmacyCatalogUser(['view pos']));

    $this->get(route('pharmacy.pos.index'))->assertOk();
});
