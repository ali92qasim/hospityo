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

function bindTenantWithModules(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function moduleGateUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Module Gate User',
        'email' => 'module-gate-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

it('blocks pharmacy pos when tenant lacks pharmacy module', function () {
    bindTenantWithModules(['visits', 'patients']);
    $this->actingAs(moduleGateUser(['dispense pharmacy']));

    $this->get(route('pharmacy.pos.index'))
        ->assertForbidden();
});

it('blocks imaging orders when tenant lacks imaging module', function () {
    bindTenantWithModules(['laboratory', 'visits']);
    $this->actingAs(moduleGateUser(['view investigation orders']));

    $this->get(route('imaging.orders.index'))
        ->assertForbidden();
});

it('blocks accounting journal entries when tenant lacks accounting module', function () {
    bindTenantWithModules(['billing', 'visits']);
    $this->actingAs(moduleGateUser(['view accounting']));

    $this->get(route('accounting.journal-entries'))
        ->assertForbidden();
});

it('allows pharmacy pos when tenant has pharmacy module', function () {
    bindTenantWithModules(['pharmacy']);
    $this->actingAs(moduleGateUser(['dispense pharmacy']));

    $this->get(route('pharmacy.pos.index'))
        ->assertOk()
        ->assertSee('POS Counter');
});
