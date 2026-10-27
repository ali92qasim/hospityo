<?php

use App\Models\Tenant;
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
function seedLandlordTenantForDeletePatientsBackfill(): Tenant
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
        'name' => 'Patients Backfill Clinic',
        'slug' => 'patients-backfill-clinic',
        'domain' => 'patients-backfill-clinic.test',
        'database' => ':memory:',
        'status' => 'active',
    ]);
}

/**
 * Hospital Administrator holds view patients (backfill target). Records Clerk
 * holds view patients only (report-only). Patient Deleter already holds delete
 * patients. SA holds view patients but is excluded from the report by name.
 *
 * @return array<string, Role>
 */
function seedRolesForDeletePatientsBackfill(bool $withAdmin = true): array
{
    Permission::findOrCreate('view patients', 'web');
    Permission::findOrCreate('restore patients', 'web');

    $roles = [];

    $roles['Super Admin'] = Role::findOrCreate('Super Admin', 'web');
    $roles['Super Admin']->givePermissionTo('view patients');

    if ($withAdmin) {
        $roles['Hospital Administrator'] = Role::findOrCreate('Hospital Administrator', 'web');
        $roles['Hospital Administrator']->givePermissionTo('view patients');
    }

    $roles['Records Clerk'] = Role::findOrCreate('Records Clerk', 'web');
    $roles['Records Clerk']->givePermissionTo('view patients');

    Permission::findOrCreate('delete patients', 'web');
    $roles['Patient Deleter'] = Role::findOrCreate('Patient Deleter', 'web');
    $roles['Patient Deleter']->givePermissionTo(['view patients', 'delete patients']);

    return $roles;
}

function roleHoldsDeletePatients(Role $role): bool
{
    return $role->fresh()->permissions()->where('name', 'delete patients')->exists();
}

const DELETE_PATIENTS_RECORDS_CLERK_LINE = '[report only] Tenant patients-backfill-clinic: role "Records Clerk" holds a patient permission but not delete patients; not granted.';

it('creates the permission and grants it to Hospital Administrator only', function () {
    seedLandlordTenantForDeletePatientsBackfill();
    Tenant::forgetCurrent();

    Permission::findOrCreate('view patients', 'web');
    $admin = Role::findOrCreate('Hospital Administrator', 'web');
    $admin->givePermissionTo('view patients');
    $clerk = Role::findOrCreate('Records Clerk', 'web');
    $clerk->givePermissionTo('view patients');

    Permission::query()->where('name', 'delete patients')->delete();

    expect(Permission::where('name', 'delete patients')->exists())->toBeFalse();

    $this->artisan('rbac:backfill-delete-patients')
        ->expectsOutputToContain('Backfilled tenant patients-backfill-clinic (1 role(s) updated).')
        ->expectsOutputToContain(DELETE_PATIENTS_RECORDS_CLERK_LINE)
        ->assertSuccessful();

    expect(Permission::where('name', 'delete patients')->where('guard_name', 'web')->exists())->toBeTrue()
        ->and(roleHoldsDeletePatients($admin))->toBeTrue()
        ->and(roleHoldsDeletePatients($clerk))->toBeFalse()
        ->and(Tenant::checkCurrent())->toBeFalse();
});

it('does not grant restore patients', function () {
    seedLandlordTenantForDeletePatientsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForDeletePatientsBackfill();

    $this->artisan('rbac:backfill-delete-patients')->assertSuccessful();

    expect(roleHoldsDeletePatients($roles['Hospital Administrator']))->toBeTrue()
        ->and($roles['Hospital Administrator']->fresh()->permissions()->where('name', 'restore patients')->exists())->toBeFalse()
        ->and($roles['Records Clerk']->fresh()->permissions()->where('name', 'restore patients')->exists())->toBeFalse();
});

it('is idempotent: a second run updates no roles and leaves other roles untouched', function () {
    seedLandlordTenantForDeletePatientsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForDeletePatientsBackfill();

    $this->artisan('rbac:backfill-delete-patients')->assertSuccessful();

    $this->artisan('rbac:backfill-delete-patients')
        ->expectsOutputToContain('Backfilled tenant patients-backfill-clinic (0 role(s) updated).')
        ->expectsOutputToContain(DELETE_PATIENTS_RECORDS_CLERK_LINE)
        ->assertSuccessful();

    expect(Permission::where('name', 'delete patients')->count())->toBe(1)
        ->and(roleHoldsDeletePatients($roles['Hospital Administrator']))->toBeTrue()
        ->and(roleHoldsDeletePatients($roles['Records Clerk']))->toBeFalse()
        ->and(roleHoldsDeletePatients($roles['Super Admin']))->toBeFalse()
        ->and(roleHoldsDeletePatients($roles['Patient Deleter']))->toBeTrue()
        ->and($roles['Records Clerk']->fresh()->hasPermissionTo('view patients'))->toBeTrue();
});

it('backfills the current tenant and forgets its isolated Spatie cache', function () {
    $tenant = seedLandlordTenantForDeletePatientsBackfill();
    $tenant->makeCurrent();

    Permission::findOrCreate('view patients', 'web');
    $admin = Role::findOrCreate('Hospital Administrator', 'web');
    $admin->givePermissionTo('view patients');
    Role::findOrCreate('Records Clerk', 'web')->givePermissionTo('view patients');
    Permission::query()->where('name', 'delete patients')->delete();

    $registrar = app(PermissionRegistrar::class);
    $registrar->cacheKey = config('permission.cache.key');
    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());

    try {
        $this->artisan('rbac:backfill-delete-patients')
            ->expectsOutputToContain('Backfilled tenant patients-backfill-clinic (1 role(s) updated).')
            ->expectsOutputToContain(DELETE_PATIENTS_RECORDS_CLERK_LINE)
            ->assertSuccessful();

        expect($registrar->cacheKey)->toBe($tenantCacheKey)
            ->and(Cache::get($tenantCacheKey))->toBeNull()
            ->and(roleHoldsDeletePatients($admin))->toBeTrue();
    } finally {
        Tenant::forgetCurrent();
    }
});

it('reports the roles it would update on --dry-run and writes nothing', function () {
    $tenant = seedLandlordTenantForDeletePatientsBackfill();
    Tenant::forgetCurrent();

    Permission::findOrCreate('view patients', 'web');
    $admin = Role::findOrCreate('Hospital Administrator', 'web');
    $admin->givePermissionTo('view patients');
    $clerk = Role::findOrCreate('Records Clerk', 'web');
    $clerk->givePermissionTo('view patients');
    Permission::query()->where('name', 'delete patients')->delete();

    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());
    $permissionCountBefore = Permission::count();
    $pivotCountBefore = $admin->permissions()->count();

    $this->artisan('rbac:backfill-delete-patients', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant patients-backfill-clinic: would update 1 role(s) (Hospital Administrator).')
        ->expectsOutputToContain(DELETE_PATIENTS_RECORDS_CLERK_LINE)
        ->doesntExpectOutputToContain('Backfilled tenant')
        ->assertSuccessful();

    expect(Permission::where('name', 'delete patients')->exists())->toBeFalse()
        ->and(Permission::count())->toBe($permissionCountBefore)
        ->and($admin->fresh()->permissions()->count())->toBe($pivotCountBefore)
        ->and(Cache::get($tenantCacheKey))->toBe(['stale' => true])
        ->and(roleHoldsDeletePatients($admin))->toBeFalse()
        ->and(roleHoldsDeletePatients($clerk))->toBeFalse();
});

it('safety report lists only non-SA/HA roles lacking delete patients and never grants', function () {
    seedLandlordTenantForDeletePatientsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForDeletePatientsBackfill();

    $this->artisan('rbac:backfill-delete-patients', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant patients-backfill-clinic: would update 1 role(s) (Hospital Administrator).')
        ->expectsOutputToContain(DELETE_PATIENTS_RECORDS_CLERK_LINE)
        ->doesntExpectOutputToContain('role "Patient Deleter"')
        ->doesntExpectOutputToContain('role "Super Admin"')
        ->doesntExpectOutputToContain('role "Hospital Administrator"')
        ->assertSuccessful();

    expect(roleHoldsDeletePatients($roles['Records Clerk']))->toBeFalse()
        ->and(roleHoldsDeletePatients($roles['Hospital Administrator']))->toBeFalse();
});

it('updates zero roles when the tenant has no Hospital Administrator role and still reports Records Clerk', function () {
    seedLandlordTenantForDeletePatientsBackfill();
    Tenant::forgetCurrent();
    seedRolesForDeletePatientsBackfill(withAdmin: false);

    $this->artisan('rbac:backfill-delete-patients')
        ->expectsOutputToContain('Backfilled tenant patients-backfill-clinic (0 role(s) updated).')
        ->expectsOutputToContain(DELETE_PATIENTS_RECORDS_CLERK_LINE)
        ->assertSuccessful();

    expect(Role::where('name', 'Hospital Administrator')->exists())->toBeFalse()
        ->and(roleHoldsDeletePatients(Role::findByName('Records Clerk', 'web')))->toBeFalse();
});
