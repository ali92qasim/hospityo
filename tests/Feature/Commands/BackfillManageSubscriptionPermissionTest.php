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
function seedLandlordTenantForSubscriptionBackfill(): Tenant
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
        'name' => 'Subscription Backfill Clinic',
        'slug' => 'subscription-backfill-clinic',
        'domain' => 'subscription-backfill-clinic.test',
        'database' => ':memory:',
        'status' => 'active',
    ]);
}

/**
 * @return array<string, Role>
 */
function seedRolesForSubscriptionBackfill(): array
{
    Permission::findOrCreate('view patients', 'web');

    $roles = [];
    foreach (['Super Admin', 'Hospital Administrator', 'Receptionist', 'Front Desk Lead'] as $name) {
        $roles[$name] = Role::findOrCreate($name, 'web');
        $roles[$name]->givePermissionTo('view patients');
    }

    return $roles;
}

function roleHoldsManageSubscription(Role $role): bool
{
    return $role->fresh()->permissions()->where('name', 'manage subscription')->exists();
}

it('registers manage subscription under the settings module', function () {
    expect(PermissionRegistry::forModule('settings'))->toContain('manage subscription')
        ->and(PermissionRegistry::flat())->toContain('manage subscription');
});

it('seeds manage subscription for Super Admin and Hospital Administrator only', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(Permission::where('name', 'manage subscription')->where('guard_name', 'web')->exists())->toBeTrue()
        ->and(Role::findByName('Super Admin', 'web')->hasPermissionTo('manage subscription'))->toBeTrue()
        ->and(Role::findByName('Hospital Administrator', 'web')->hasPermissionTo('manage subscription'))->toBeTrue();

    foreach (['Doctor', 'Nurse', 'Receptionist', 'Pharmacist', 'Lab Technician'] as $name) {
        expect(Role::findByName($name, 'web')->hasPermissionTo('manage subscription'))
            ->toBeFalse("{$name} must not hold manage subscription");
    }
});

it('creates the permission and grants it to exactly Super Admin and Hospital Administrator', function () {
    seedLandlordTenantForSubscriptionBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForSubscriptionBackfill();

    expect(Permission::where('name', 'manage subscription')->exists())->toBeFalse();

    $this->artisan('rbac:backfill-manage-subscription')
        ->expectsOutputToContain('Backfilled tenant subscription-backfill-clinic (2 role(s) updated).')
        ->assertSuccessful();

    expect(Permission::where('name', 'manage subscription')->where('guard_name', 'web')->exists())->toBeTrue()
        ->and(roleHoldsManageSubscription($roles['Super Admin']))->toBeTrue()
        ->and(roleHoldsManageSubscription($roles['Hospital Administrator']))->toBeTrue()
        ->and(roleHoldsManageSubscription($roles['Receptionist']))->toBeFalse()
        ->and(roleHoldsManageSubscription($roles['Front Desk Lead']))->toBeFalse()
        ->and($roles['Receptionist']->fresh()->hasPermissionTo('view patients'))->toBeTrue()
        ->and(Tenant::checkCurrent())->toBeFalse();
});

it('is idempotent: a second run updates no roles and leaves other roles untouched', function () {
    seedLandlordTenantForSubscriptionBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForSubscriptionBackfill();

    $this->artisan('rbac:backfill-manage-subscription')->assertSuccessful();

    $this->artisan('rbac:backfill-manage-subscription')
        ->expectsOutputToContain('Backfilled tenant subscription-backfill-clinic (0 role(s) updated).')
        ->assertSuccessful();

    expect(Permission::where('name', 'manage subscription')->count())->toBe(1)
        ->and(roleHoldsManageSubscription($roles['Super Admin']))->toBeTrue()
        ->and(roleHoldsManageSubscription($roles['Hospital Administrator']))->toBeTrue()
        ->and(roleHoldsManageSubscription($roles['Receptionist']))->toBeFalse()
        ->and(roleHoldsManageSubscription($roles['Front Desk Lead']))->toBeFalse()
        ->and($roles['Hospital Administrator']->fresh()->hasPermissionTo('view patients'))->toBeTrue();
});

it('backfills the current tenant and forgets its isolated Spatie cache', function () {
    $tenant = seedLandlordTenantForSubscriptionBackfill();
    $tenant->makeCurrent();
    $roles = seedRolesForSubscriptionBackfill();

    $registrar = app(PermissionRegistrar::class);
    $registrar->cacheKey = config('permission.cache.key');
    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());

    try {
        $this->artisan('rbac:backfill-manage-subscription')
            ->expectsOutputToContain('Backfilled tenant subscription-backfill-clinic (2 role(s) updated).')
            ->assertSuccessful();

        expect($registrar->cacheKey)->toBe($tenantCacheKey)
            ->and(Cache::get($tenantCacheKey))->toBeNull()
            ->and(roleHoldsManageSubscription($roles['Super Admin']))->toBeTrue()
            ->and(roleHoldsManageSubscription($roles['Hospital Administrator']))->toBeTrue();
    } finally {
        Tenant::forgetCurrent();
    }
});

it('reports the roles it would update on --dry-run and writes nothing', function () {
    $tenant = seedLandlordTenantForSubscriptionBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForSubscriptionBackfill();

    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());
    $permissionCountBefore = Permission::count();

    $this->artisan('rbac:backfill-manage-subscription', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant subscription-backfill-clinic: would update 2 role(s) (Super Admin, Hospital Administrator).')
        ->doesntExpectOutputToContain('Backfilled tenant')
        ->assertSuccessful();

    expect(Permission::where('name', 'manage subscription')->exists())->toBeFalse()
        ->and(Permission::count())->toBe($permissionCountBefore)
        ->and(Cache::get($tenantCacheKey))->toBe(['stale' => true]);

    foreach ($roles as $role) {
        expect(roleHoldsManageSubscription($role))->toBeFalse();
    }
});

it('on --dry-run counts only target roles still lacking an existing permission', function () {
    seedLandlordTenantForSubscriptionBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForSubscriptionBackfill();

    Permission::findOrCreate('manage subscription', 'web');
    $roles['Super Admin']->givePermissionTo('manage subscription');

    $this->artisan('rbac:backfill-manage-subscription', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant subscription-backfill-clinic: would update 1 role(s) (Hospital Administrator).')
        ->assertSuccessful();

    expect(roleHoldsManageSubscription($roles['Hospital Administrator']))->toBeFalse()
        ->and(roleHoldsManageSubscription($roles['Receptionist']))->toBeFalse();
});
