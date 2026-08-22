<?php

use App\Models\Tenant;
use App\Models\User;
use App\Services\SidebarService;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->service = new SidebarService;
});

function pharmacySidebarTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class);
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => $module === 'pharmacy');

    return $tenant;
}

it('shows POS sidebar link that opens in a new tab', function () {
    $user = User::create([
        'name' => 'POS Nav User',
        'email' => 'pos-nav-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('dispense pharmacy', 'web');
    $user->givePermissionTo('dispense pharmacy');

    $menu = $this->service->build($user, pharmacySidebarTenant());
    $pharmacy = collect($menu)->firstWhere('label', 'Pharmacy');
    $pos = collect($pharmacy['items'])->firstWhere('label', 'POS');

    expect($pos)->not->toBeNull()
        ->and($pos['route'])->toBe('pharmacy.pos.index')
        ->and($pos['open_in_new_tab'])->toBeTrue();
});

it('renders pos page without admin sidebar', function () {
    $tenant = new Tenant;
    $tenant->id = 1;
    $tenant->status = 'active';
    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    $user = User::create([
        'name' => 'POS Layout User',
        'email' => 'pos-layout-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('dispense pharmacy', 'web');
    $user->givePermissionTo('dispense pharmacy');

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($user)
        ->get(route('pharmacy.pos.index'))
        ->assertOk()
        ->assertSee('POS Counter')
        ->assertSee('Prescription')
        ->assertSee('Counter Sale')
        ->assertDontSee('id="sidebar"', false);
});
