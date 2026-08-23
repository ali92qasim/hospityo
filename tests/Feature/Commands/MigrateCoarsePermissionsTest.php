<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Support\PermissionRegistry;

beforeEach(function () {
    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'multitenancy.switch_tenant_tasks' => [],
        'permission.testing' => true,
    ]);

    $this->app['db']->purge('landlord');

    $this->artisan('migrate', [
        '--path' => 'database/migrations/landlord/2026_03_30_145800_create_landlord_tenants_table.php',
        '--database' => 'landlord',
    ]);

    Tenant::create([
        'name' => 'Test Clinic',
        'slug' => 'test-clinic',
        'domain' => 'test-clinic.test',
        'database' => ':memory:',
        'status' => 'active',
    ]);
});

function migrateCoarseRole(string $coarsePermission, string $roleName = 'Legacy Role'): Role
{
    Permission::findOrCreate($coarsePermission, 'web');

    $role = Role::create([
        'name' => $roleName,
        'guard_name' => 'web',
    ]);

    $role->givePermissionTo($coarsePermission);

    return $role;
}

function hrViewPermissions(): array
{
    $groups = PermissionRegistry::grouped()['hr']['groups'] ?? [];

    return collect($groups)
        ->flatten()
        ->filter(fn (string $permission) => str_starts_with($permission, 'view ') && $permission !== 'view hr')
        ->values()
        ->all();
}

it('migrates view hr to granular hr view permissions', function () {
    $role = migrateCoarseRole('view hr');

    $this->artisan('tenants:migrate-coarse-permissions', ['--tenant' => 'test-clinic'])
        ->assertSuccessful();

    $role->refresh();

    foreach (hrViewPermissions() as $permission) {
        expect($role->hasPermissionTo($permission))->toBeTrue("Expected {$permission}");
    }

    expect($role->hasPermissionTo('view hr'))->toBeTrue();
});

it('migrates view accounting to granular accounting view permissions', function () {
    $role = migrateCoarseRole('view accounting');

    $this->artisan('tenants:migrate-coarse-permissions', ['--tenant' => 'test-clinic'])
        ->assertSuccessful();

    $role->refresh();

    expect($role->hasPermissionTo('view chart of accounts'))->toBeTrue();
    expect($role->hasPermissionTo('view journal entries'))->toBeTrue();
    expect($role->hasPermissionTo('view fiscal years'))->toBeTrue();
    expect($role->hasPermissionTo('view accounting'))->toBeTrue();
});

it('migrates manage doctor shares to view create and edit permissions', function () {
    $role = migrateCoarseRole('manage doctor shares');

    $this->artisan('tenants:migrate-coarse-permissions', ['--tenant' => 'test-clinic'])
        ->assertSuccessful();

    $role->refresh();
    $role->load('permissions');

    $permissionNames = $role->permissions->pluck('name');

    expect($permissionNames)->toContain('view share rules');
    expect($permissionNames)->toContain('create share rules');
    expect($permissionNames)->toContain('edit share rules');
    expect($permissionNames)->not->toContain('delete share rules');
    expect($permissionNames)->not->toContain('approve settlements');
});

it('migrates manage backup to all backup permissions', function () {
    $role = migrateCoarseRole('manage backup');

    $this->artisan('tenants:migrate-coarse-permissions', ['--tenant' => 'test-clinic'])
        ->assertSuccessful();

    $role->refresh();

    foreach (['view backup', 'create backup', 'restore backup', 'delete backup', 'manage backup'] as $permission) {
        expect($role->hasPermissionTo($permission))->toBeTrue("Expected {$permission}");
    }
});

it('migrates manage settings to view and edit settings', function () {
    $role = migrateCoarseRole('manage settings');

    $this->artisan('tenants:migrate-coarse-permissions', ['--tenant' => 'test-clinic'])
        ->assertSuccessful();

    $role->refresh();

    expect($role->hasPermissionTo('view settings'))->toBeTrue();
    expect($role->hasPermissionTo('edit settings'))->toBeTrue();
    expect($role->hasPermissionTo('manage settings'))->toBeTrue();
});

it('migrates view pharmacy to granular pharmacy view permissions', function () {
    $role = migrateCoarseRole('view pharmacy');

    $this->artisan('tenants:migrate-coarse-permissions', ['--tenant' => 'test-clinic'])
        ->assertSuccessful();

    $role->refresh();
    $role->load('permissions');

    $permissionNames = $role->permissions->pluck('name');

    expect($permissionNames)->toContain('view medicines');
    expect($permissionNames)->toContain('view prescriptions');
    expect($permissionNames)->toContain('view pos');
    expect($permissionNames)->not->toContain('manage pharmacy');
});

it('is idempotent when granular permissions already exist', function () {
    $role = migrateCoarseRole('view hr');
    Permission::findOrCreate('view employees', 'web');
    $role->givePermissionTo('view employees');

    $beforeCount = $role->permissions()->count();

    $this->artisan('tenants:migrate-coarse-permissions', ['--tenant' => 'test-clinic'])
        ->assertSuccessful();

    $this->artisan('tenants:migrate-coarse-permissions', ['--tenant' => 'test-clinic'])
        ->assertSuccessful();

    $role->refresh();

    expect($role->permissions()->count())->toBeGreaterThanOrEqual($beforeCount);
    expect($role->hasPermissionTo('view employees'))->toBeTrue();
    expect($role->hasPermissionTo('view payroll runs'))->toBeTrue();
});

it('skips roles without coarse permissions', function () {
    $role = Role::create(['name' => 'Unrelated', 'guard_name' => 'web']);
    Permission::findOrCreate('view patients', 'web');
    $role->givePermissionTo('view patients');

    $this->artisan('tenants:migrate-coarse-permissions', ['--tenant' => 'test-clinic'])
        ->assertSuccessful();

    $role->refresh();
    $role->load('permissions');

    $permissionNames = $role->permissions->pluck('name');

    expect($permissionNames)->toContain('view patients');
    expect($permissionNames)->not->toContain('view employees');
});

it('fails when tenant slug is not found', function () {
    $this->artisan('tenants:migrate-coarse-permissions', ['--tenant' => 'missing-clinic'])
        ->assertFailed();
});
