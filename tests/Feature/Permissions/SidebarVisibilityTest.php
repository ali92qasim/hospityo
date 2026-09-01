<?php

use App\Models\Tenant;
use App\Models\User;
use App\Services\SidebarService;
use App\Support\PermissionRegistry;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->service = new SidebarService;

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
});

function sidebarTenantWithModules(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class);
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    return $tenant;
}

function sidebarUserWithPermissions(array $permissions, ?string $role = null): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Sidebar User',
        'email' => 'sidebar-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    if ($permissions !== []) {
        $user->givePermissionTo($permissions);
    }

    if ($role !== null) {
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);
    }

    return $user;
}

function sidebarMenuLabels(User $user, Tenant $tenant): array
{
    return collect(app(SidebarService::class)->build($user, $tenant))
        ->pluck('label')
        ->all();
}

it('shows hr group for hospital administrator with view employees', function () {
    $user = sidebarUserWithPermissions(['view employees'], 'Hospital Administrator');
    $tenant = sidebarTenantWithModules(['hr']);

    $labels = sidebarMenuLabels($user, $tenant);

    expect($labels)->toContain('HR');

    $hrGroup = collect($this->service->build($user, $tenant))->firstWhere('label', 'HR');

    expect(collect($hrGroup['items'])->pluck('label'))->toContain('Employees');
});

it('hides pharmacy group when tenant lacks pharmacy module', function () {
    $user = sidebarUserWithPermissions(['view medicines', 'view pos', 'dispense pharmacy']);
    $tenant = sidebarTenantWithModules(['patients', 'visits', 'billing']);

    $labels = sidebarMenuLabels($user, $tenant);

    expect($labels)->not->toContain('Pharmacy');
});

it('hides hr group when user lacks employee permissions even with hr module', function () {
    $user = sidebarUserWithPermissions(['view patients']);
    $tenant = sidebarTenantWithModules(['hr', 'patients']);

    $labels = sidebarMenuLabels($user, $tenant);

    expect($labels)->not->toContain('HR');
});

it('shows pharmacy group when tenant has pharmacy module and user has pharmacy permissions', function () {
    $user = sidebarUserWithPermissions(['view medicines']);
    $tenant = sidebarTenantWithModules(['pharmacy']);

    $labels = sidebarMenuLabels($user, $tenant);

    expect($labels)->toContain('Pharmacy');
});

it('renders hr sidebar link on dashboard for authorized user', function () {
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, ['hr', 'patients'], true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    $user = sidebarUserWithPermissions(['view employees'], 'Hospital Administrator');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('HR', false)
        ->assertSee('Employees', false);
});

it('does not render pharmacy on dashboard when tenant lacks pharmacy module', function () {
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => $module !== 'pharmacy');

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    $user = sidebarUserWithPermissions(['view medicines', 'view pos']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('>Pharmacy<', false);
});

it('hides departments when the tenant plan omits the departments module', function () {
    $user = sidebarUserWithPermissions(['view departments']);
    $tenant = sidebarTenantWithModules(['patients', 'visits']);

    expect(sidebarMenuLabels($user, $tenant))->not->toContain('Departments');
});

it('shows departments when the tenant has the departments module and the user can view them', function () {
    $user = sidebarUserWithPermissions(['view departments']);
    $tenant = sidebarTenantWithModules(['departments']);

    expect(sidebarMenuLabels($user, $tenant))->toContain('Departments');
});

it('hides every saas-gated sidebar section when the tenant has no modules', function () {
    $user = sidebarUserWithPermissions(PermissionRegistry::all());
    $tenant = sidebarTenantWithModules([]);

    expect(sidebarMenuLabels($user, $tenant))->toBe(['Dashboard']);
});

it('shows settings group when the user can access any section and hides it otherwise', function () {
    Permission::findOrCreate('access settings.hospital-info', 'web');
    $user = sidebarUserWithPermissions(['access settings.hospital-info']);
    $tenant = sidebarTenantWithModules(['settings', 'settings.hospital-info']);

    $menu = $this->service->build($user, $tenant);
    $group = collect($menu)->firstWhere('id', 'settings');

    expect($group)->not->toBeNull()
        ->and(collect($group['items'])->pluck('label')->all())->toBe(['Hospital Info']);

    $denied = sidebarUserWithPermissions([]);
    expect(collect($this->service->build($denied, $tenant))->pluck('id'))->not->toContain('settings');
});

it('hides the settings group when the tenant plan omits the settings module', function () {
    Permission::findOrCreate('access settings', 'web');
    $user = sidebarUserWithPermissions(['access settings']);
    $tenant = sidebarTenantWithModules(['patients']);

    expect(collect($this->service->build($user, $tenant))->pluck('id'))->not->toContain('settings');
});

it('shows only settings children that the tenant plan grants', function () {
    $user = sidebarUserWithPermissions(['access settings']);
    $tenant = sidebarTenantWithModules(['settings', 'settings.hospital-info']);

    $group = collect($this->service->build($user, $tenant))->firstWhere('id', 'settings');

    expect($group)->not->toBeNull()
        ->and(collect($group['items'])->pluck('label')->all())->toBe(['Hospital Info']);
});

it('hides emergency when the tenant has visits but not the emergency module', function () {
    $user = sidebarUserWithPermissions(['view visits']);
    $tenant = sidebarTenantWithModules(['visits']);

    $labels = sidebarMenuLabels($user, $tenant);

    expect($labels)->toContain('OPD')
        ->and($labels)->not->toContain('Emergency');
});

it('shows emergency independently of the visits module', function () {
    $user = sidebarUserWithPermissions(['view visits']);
    $tenant = sidebarTenantWithModules(['emergency']);

    $labels = sidebarMenuLabels($user, $tenant);

    expect($labels)->toContain('Emergency')
        ->and($labels)->not->toContain('OPD');
});
