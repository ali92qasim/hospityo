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
function seedLandlordTenantForAccountingPaymentsBackfill(): Tenant
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
        'name' => 'Accounting Payments Backfill Clinic',
        'slug' => 'accounting-payments-backfill-clinic',
        'domain' => 'accounting-payments-backfill-clinic.test',
        'database' => ':memory:',
        'status' => 'active',
    ]);
}

/**
 * @return array<string, Role>
 */
function seedRolesForAccountingPaymentsBackfill(): array
{
    Permission::findOrCreate('view journal entries', 'web');

    $roles = [];
    foreach (['Super Admin', 'Hospital Administrator', 'Nurse', 'Doctor'] as $name) {
        $roles[$name] = Role::findOrCreate($name, 'web');
        $roles[$name]->givePermissionTo('view journal entries');
    }

    return $roles;
}

function roleHoldsViewAccountingPayments(Role $role): bool
{
    return $role->fresh()->permissions()->where('name', 'view accounting payments')->exists();
}

it('registers view accounting payments under the accounting module', function () {
    expect(PermissionRegistry::forModule('accounting'))->toContain('view accounting payments')
        ->and(PermissionRegistry::flat())->toContain('view accounting payments');
});

it('lists view accounting payments in the seeder catalog', function () {
    expect(RolePermissionSeeder::catalogPermissions())->toContain('view accounting payments');
});

it('seeds view accounting payments for Super Admin and Hospital Administrator', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(Permission::where('name', 'view accounting payments')->where('guard_name', 'web')->exists())->toBeTrue()
        ->and(Role::findByName('Super Admin', 'web')->hasPermissionTo('view accounting payments'))->toBeTrue()
        ->and(Role::findByName('Hospital Administrator', 'web')->hasPermissionTo('view accounting payments'))->toBeTrue();
});

it('creates the permission and grants it to exactly Super Admin and Hospital Administrator', function () {
    seedLandlordTenantForAccountingPaymentsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForAccountingPaymentsBackfill();

    expect(Permission::where('name', 'view accounting payments')->exists())->toBeFalse();

    $this->artisan('rbac:backfill-view-accounting-payments')
        ->expectsOutputToContain('Backfilled tenant accounting-payments-backfill-clinic (2 role(s) updated).')
        ->assertSuccessful();

    expect(Permission::where('name', 'view accounting payments')->where('guard_name', 'web')->exists())->toBeTrue()
        ->and(roleHoldsViewAccountingPayments($roles['Super Admin']))->toBeTrue()
        ->and(roleHoldsViewAccountingPayments($roles['Hospital Administrator']))->toBeTrue()
        ->and(roleHoldsViewAccountingPayments($roles['Nurse']))->toBeFalse()
        ->and(roleHoldsViewAccountingPayments($roles['Doctor']))->toBeFalse()
        ->and($roles['Nurse']->fresh()->hasPermissionTo('view journal entries'))->toBeTrue()
        ->and(Tenant::checkCurrent())->toBeFalse();
});

it('is idempotent: a second run updates no roles and leaves other roles untouched', function () {
    seedLandlordTenantForAccountingPaymentsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForAccountingPaymentsBackfill();

    $this->artisan('rbac:backfill-view-accounting-payments')->assertSuccessful();

    $this->artisan('rbac:backfill-view-accounting-payments')
        ->expectsOutputToContain('Backfilled tenant accounting-payments-backfill-clinic (0 role(s) updated).')
        ->assertSuccessful();

    expect(Permission::where('name', 'view accounting payments')->count())->toBe(1)
        ->and(roleHoldsViewAccountingPayments($roles['Super Admin']))->toBeTrue()
        ->and(roleHoldsViewAccountingPayments($roles['Hospital Administrator']))->toBeTrue()
        ->and(roleHoldsViewAccountingPayments($roles['Nurse']))->toBeFalse()
        ->and(roleHoldsViewAccountingPayments($roles['Doctor']))->toBeFalse()
        ->and($roles['Hospital Administrator']->fresh()->hasPermissionTo('view journal entries'))->toBeTrue();
});

it('backfills the current tenant and forgets its isolated Spatie cache', function () {
    $tenant = seedLandlordTenantForAccountingPaymentsBackfill();
    $tenant->makeCurrent();
    $roles = seedRolesForAccountingPaymentsBackfill();

    $registrar = app(PermissionRegistrar::class);
    $registrar->cacheKey = config('permission.cache.key');
    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());

    try {
        $this->artisan('rbac:backfill-view-accounting-payments')
            ->expectsOutputToContain('Backfilled tenant accounting-payments-backfill-clinic (2 role(s) updated).')
            ->assertSuccessful();

        expect($registrar->cacheKey)->toBe($tenantCacheKey)
            ->and(Cache::get($tenantCacheKey))->toBeNull()
            ->and(roleHoldsViewAccountingPayments($roles['Super Admin']))->toBeTrue()
            ->and(roleHoldsViewAccountingPayments($roles['Hospital Administrator']))->toBeTrue()
            ->and(roleHoldsViewAccountingPayments($roles['Nurse']))->toBeFalse();
    } finally {
        Tenant::forgetCurrent();
    }
});

it('reports the roles it would update on --dry-run and writes nothing', function () {
    $tenant = seedLandlordTenantForAccountingPaymentsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForAccountingPaymentsBackfill();

    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());
    $permissionCountBefore = Permission::count();

    $this->artisan('rbac:backfill-view-accounting-payments', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant accounting-payments-backfill-clinic: would update 2 role(s) (Super Admin, Hospital Administrator).')
        ->doesntExpectOutputToContain('Backfilled tenant')
        ->assertSuccessful();

    expect(Permission::where('name', 'view accounting payments')->exists())->toBeFalse()
        ->and(Permission::count())->toBe($permissionCountBefore)
        ->and(Cache::get($tenantCacheKey))->toBe(['stale' => true]);

    foreach ($roles as $role) {
        expect(roleHoldsViewAccountingPayments($role))->toBeFalse();
    }
});

it('on --dry-run counts only target roles still lacking an existing permission', function () {
    seedLandlordTenantForAccountingPaymentsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForAccountingPaymentsBackfill();

    Permission::findOrCreate('view accounting payments', 'web');
    $roles['Super Admin']->givePermissionTo('view accounting payments');

    $this->artisan('rbac:backfill-view-accounting-payments', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant accounting-payments-backfill-clinic: would update 1 role(s) (Hospital Administrator).')
        ->assertSuccessful();

    expect(roleHoldsViewAccountingPayments($roles['Hospital Administrator']))->toBeFalse()
        ->and(roleHoldsViewAccountingPayments($roles['Nurse']))->toBeFalse();
});
