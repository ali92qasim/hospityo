<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use Database\Seeders\RolePermissionSeeder;

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

function seederPermissionNames(): array
{
    return (new ReflectionClass(RolePermissionSeeder::class))->getConstant('PERMISSIONS');
}

it('is idempotent when run twice', function () {
    $this->artisan('tenants:sync-permissions', ['--tenant' => 'test-clinic'])
        ->assertSuccessful();

    $permissionCount = Permission::count();
    $hospitalAdmin = Role::where('name', 'Hospital Administrator')->first();
    $hospitalAdminPermissions = $hospitalAdmin->permissions()->pluck('name')->sort()->values()->all();
    $superAdminPermissionCount = Role::where('name', 'Super Admin')->first()->permissions()->count();

    $this->artisan('tenants:sync-permissions', ['--tenant' => 'test-clinic'])
        ->assertSuccessful();

    expect(Permission::count())->toBe($permissionCount)
        ->and(Role::where('name', 'Super Admin')->first()->permissions()->count())->toBe($superAdminPermissionCount)
        ->and($hospitalAdmin->fresh()->permissions()->pluck('name')->sort()->values()->all())
        ->toBe($hospitalAdminPermissions);
});

it('creates permissions from RolePermissionSeeder on first run', function () {
    expect(Permission::where('name', 'view employees')->exists())->toBeFalse();

    $this->artisan('tenants:sync-permissions', ['--tenant' => 'test-clinic'])
        ->assertSuccessful();

    foreach (seederPermissionNames() as $permission) {
        expect(Permission::where('name', $permission)->exists())->toBeTrue("Missing permission: {$permission}");
    }
});

it('restores a removed seeder permission on subsequent sync', function () {
    $this->artisan('tenants:sync-permissions', ['--tenant' => 'test-clinic'])
        ->assertSuccessful();

    Permission::where('name', 'view employees')->delete();

    expect(Permission::where('name', 'view employees')->exists())->toBeFalse();

    $this->artisan('tenants:sync-permissions', ['--tenant' => 'test-clinic'])
        ->assertSuccessful();

    expect(Permission::where('name', 'view employees')->exists())->toBeTrue();
});

it('fails when tenant slug is not found', function () {
    $this->artisan('tenants:sync-permissions', ['--tenant' => 'missing-clinic'])
        ->assertFailed();
});
