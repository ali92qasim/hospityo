<?php

use App\Models\Tenant;
use App\Support\PermissionRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Isolated in-memory landlord with one tenant whose connection is the test
 * tenant connection (no switch tasks), so the command's all-tenants path runs
 * against the RefreshDatabase sqlite connection only.
 */
function seedLandlordTenantForTheatresBackfill(): Tenant
{
    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'multitenancy.switch_tenant_tasks' => [],
    ]);

    app('db')->purge('landlord');

    Artisan::call('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);

    return Tenant::create([
        'name' => 'Theatres Backfill Clinic',
        'slug' => 'theatres-backfill-clinic',
        'domain' => 'theatres-backfill-clinic.test',
        'database' => ':memory:',
        'status' => 'active',
    ]);
}

/**
 * @return array<string, Role>
 */
function seedRolesForTheatresBackfill(): array
{
    Permission::findOrCreate('view surgeries', 'web');

    $roles = [];
    foreach (['Super Admin', 'Hospital Administrator', 'Nurse', 'Theatre Coordinator'] as $name) {
        $roles[$name] = Role::findOrCreate($name, 'web');
        $roles[$name]->givePermissionTo('view surgeries');
    }

    return $roles;
}

function roleHoldsManageTheatres(Role $role): bool
{
    return $role->fresh()->permissions()->where('name', 'manage theatres')->exists();
}

it('registers manage theatres under the ot module', function () {
    expect(PermissionRegistry::forModule('ot'))->toContain('manage theatres')
        ->and(PermissionRegistry::flat())->toContain('manage theatres');
});

it('seeds manage theatres for Super Admin and Hospital Administrator only', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(Permission::where('name', 'manage theatres')->where('guard_name', 'web')->exists())->toBeTrue()
        ->and(Role::findByName('Super Admin', 'web')->hasPermissionTo('manage theatres'))->toBeTrue()
        ->and(Role::findByName('Hospital Administrator', 'web')->hasPermissionTo('manage theatres'))->toBeTrue();

    foreach (['Doctor', 'Nurse', 'Receptionist', 'Pharmacist', 'Lab Technician'] as $name) {
        expect(Role::findByName($name, 'web')->hasPermissionTo('manage theatres'))
            ->toBeFalse("{$name} must not hold manage theatres");
    }
});

it('creates the permission and grants it to exactly Super Admin and Hospital Administrator', function () {
    seedLandlordTenantForTheatresBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForTheatresBackfill();

    expect(Permission::where('name', 'manage theatres')->exists())->toBeFalse();

    $this->artisan('rbac:backfill-manage-theatres')
        ->expectsOutputToContain('Backfilled tenant theatres-backfill-clinic (2 role(s) updated).')
        ->assertSuccessful();

    expect(Permission::where('name', 'manage theatres')->where('guard_name', 'web')->exists())->toBeTrue()
        ->and(roleHoldsManageTheatres($roles['Super Admin']))->toBeTrue()
        ->and(roleHoldsManageTheatres($roles['Hospital Administrator']))->toBeTrue()
        ->and(roleHoldsManageTheatres($roles['Nurse']))->toBeFalse()
        ->and(roleHoldsManageTheatres($roles['Theatre Coordinator']))->toBeFalse()
        ->and($roles['Nurse']->fresh()->hasPermissionTo('view surgeries'))->toBeTrue()
        ->and(Tenant::checkCurrent())->toBeFalse();
});

it('is idempotent: a second run updates no roles and leaves other roles untouched', function () {
    seedLandlordTenantForTheatresBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForTheatresBackfill();

    $this->artisan('rbac:backfill-manage-theatres')->assertSuccessful();

    $this->artisan('rbac:backfill-manage-theatres')
        ->expectsOutputToContain('Backfilled tenant theatres-backfill-clinic (0 role(s) updated).')
        ->assertSuccessful();

    expect(Permission::where('name', 'manage theatres')->count())->toBe(1)
        ->and(roleHoldsManageTheatres($roles['Super Admin']))->toBeTrue()
        ->and(roleHoldsManageTheatres($roles['Hospital Administrator']))->toBeTrue()
        ->and(roleHoldsManageTheatres($roles['Nurse']))->toBeFalse()
        ->and(roleHoldsManageTheatres($roles['Theatre Coordinator']))->toBeFalse()
        ->and($roles['Hospital Administrator']->fresh()->hasPermissionTo('view surgeries'))->toBeTrue();
});

it('backfills the current tenant and forgets its isolated Spatie cache', function () {
    $tenant = seedLandlordTenantForTheatresBackfill();
    $tenant->makeCurrent();
    $roles = seedRolesForTheatresBackfill();

    $registrar = app(PermissionRegistrar::class);
    $registrar->cacheKey = config('permission.cache.key');
    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());

    try {
        $this->artisan('rbac:backfill-manage-theatres')
            ->expectsOutputToContain('Backfilled tenant theatres-backfill-clinic (2 role(s) updated).')
            ->assertSuccessful();

        expect($registrar->cacheKey)->toBe($tenantCacheKey)
            ->and(Cache::get($tenantCacheKey))->toBeNull()
            ->and(roleHoldsManageTheatres($roles['Super Admin']))->toBeTrue()
            ->and(roleHoldsManageTheatres($roles['Hospital Administrator']))->toBeTrue()
            ->and(roleHoldsManageTheatres($roles['Nurse']))->toBeFalse();
    } finally {
        Tenant::forgetCurrent();
    }
});

it('reports the roles it would update on --dry-run and writes nothing', function () {
    $tenant = seedLandlordTenantForTheatresBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForTheatresBackfill();

    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());
    $permissionCountBefore = Permission::count();

    $this->artisan('rbac:backfill-manage-theatres', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant theatres-backfill-clinic: would update 2 role(s) (Super Admin, Hospital Administrator).')
        ->doesntExpectOutputToContain('Backfilled tenant')
        ->assertSuccessful();

    expect(Permission::where('name', 'manage theatres')->exists())->toBeFalse()
        ->and(Permission::count())->toBe($permissionCountBefore)
        ->and(Cache::get($tenantCacheKey))->toBe(['stale' => true]);

    foreach ($roles as $role) {
        expect(roleHoldsManageTheatres($role))->toBeFalse();
    }
});

it('on --dry-run counts only target roles still lacking an existing permission', function () {
    seedLandlordTenantForTheatresBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForTheatresBackfill();

    Permission::findOrCreate('manage theatres', 'web');
    $roles['Super Admin']->givePermissionTo('manage theatres');

    $this->artisan('rbac:backfill-manage-theatres', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant theatres-backfill-clinic: would update 1 role(s) (Hospital Administrator).')
        ->assertSuccessful();

    expect(roleHoldsManageTheatres($roles['Hospital Administrator']))->toBeFalse()
        ->and(roleHoldsManageTheatres($roles['Nurse']))->toBeFalse();
});
