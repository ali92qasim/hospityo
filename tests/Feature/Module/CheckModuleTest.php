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

it('blocks emergency visit list when tenant lacks the emergency module', function () {
    bindTenantWithModules(['visits']);
    $this->actingAs(moduleGateUser(['view visits']));

    $this->get(route('visits.index', ['visit_type' => 'emergency']))
        ->assertForbidden();
});

it('allows emergency visit list when tenant has the emergency module', function () {
    bindTenantWithModules(['emergency']);
    $this->actingAs(moduleGateUser(['view visits']));

    $this->get(route('visits.index', ['visit_type' => 'emergency']))
        ->assertOk();
});

it('allows opd visit list when tenant has visits but not emergency', function () {
    bindTenantWithModules(['visits']);
    $this->actingAs(moduleGateUser(['view visits']));

    $this->get(route('visits.index', ['visit_type' => 'opd']))
        ->assertOk();
});

it('blocks hospital info settings when tenant lacks the settings child module', function () {
    bindTenantWithModules(['settings']);
    $this->actingAs(moduleGateUser(['access settings']));

    $this->get(route('settings.hospital-info'))
        ->assertForbidden();
});

it('allows hospital info settings when tenant has settings and the child module', function () {
    bindTenantWithModules(['settings', 'settings.hospital-info']);
    $this->actingAs(moduleGateUser(['access settings']));

    $this->get(route('settings.hospital-info'))
        ->assertOk();
});
