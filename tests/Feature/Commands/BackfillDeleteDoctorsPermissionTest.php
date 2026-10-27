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
function seedLandlordTenantForDeleteDoctorsBackfill(): Tenant
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
        'name' => 'Doctors Backfill Clinic',
        'slug' => 'doctors-backfill-clinic',
        'domain' => 'doctors-backfill-clinic.test',
        'database' => ':memory:',
        'status' => 'active',
    ]);
}

/**
 * Hospital Administrator holds view doctors (backfill target). Records Clerk
 * holds view doctors only (report-only). Doctor Deleter already holds delete
 * doctors. SA holds view doctors but is excluded from the report by name.
 *
 * @return array<string, Role>
 */
function seedRolesForDeleteDoctorsBackfill(bool $withAdmin = true): array
{
    Permission::findOrCreate('view doctors', 'web');
    Permission::findOrCreate('restore doctors', 'web');

    $roles = [];

    $roles['Super Admin'] = Role::findOrCreate('Super Admin', 'web');
    $roles['Super Admin']->givePermissionTo('view doctors');

    if ($withAdmin) {
        $roles['Hospital Administrator'] = Role::findOrCreate('Hospital Administrator', 'web');
        $roles['Hospital Administrator']->givePermissionTo('view doctors');
    }

    $roles['Records Clerk'] = Role::findOrCreate('Records Clerk', 'web');
    $roles['Records Clerk']->givePermissionTo('view doctors');

    Permission::findOrCreate('delete doctors', 'web');
    $roles['Doctor Deleter'] = Role::findOrCreate('Doctor Deleter', 'web');
    $roles['Doctor Deleter']->givePermissionTo(['view doctors', 'delete doctors']);

    return $roles;
}

function roleHoldsDeleteDoctors(Role $role): bool
{
    return $role->fresh()->permissions()->where('name', 'delete doctors')->exists();
}

const DELETE_DOCTORS_RECORDS_CLERK_LINE = '[report only] Tenant doctors-backfill-clinic: role "Records Clerk" holds a doctor permission but not delete doctors; not granted.';

it('creates the permission and grants it to Hospital Administrator only', function () {
    seedLandlordTenantForDeleteDoctorsBackfill();
    Tenant::forgetCurrent();

    Permission::findOrCreate('view doctors', 'web');
    $admin = Role::findOrCreate('Hospital Administrator', 'web');
    $admin->givePermissionTo('view doctors');
    $clerk = Role::findOrCreate('Records Clerk', 'web');
    $clerk->givePermissionTo('view doctors');

    Permission::query()->where('name', 'delete doctors')->delete();

    expect(Permission::where('name', 'delete doctors')->exists())->toBeFalse();

    $this->artisan('rbac:backfill-delete-doctors')
        ->expectsOutputToContain('Backfilled tenant doctors-backfill-clinic (1 role(s) updated).')
        ->expectsOutputToContain(DELETE_DOCTORS_RECORDS_CLERK_LINE)
        ->assertSuccessful();

    expect(Permission::where('name', 'delete doctors')->where('guard_name', 'web')->exists())->toBeTrue()
        ->and(roleHoldsDeleteDoctors($admin))->toBeTrue()
        ->and(roleHoldsDeleteDoctors($clerk))->toBeFalse()
        ->and(Tenant::checkCurrent())->toBeFalse();
});

it('does not grant restore doctors', function () {
    seedLandlordTenantForDeleteDoctorsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForDeleteDoctorsBackfill();

    $this->artisan('rbac:backfill-delete-doctors')->assertSuccessful();

    expect(roleHoldsDeleteDoctors($roles['Hospital Administrator']))->toBeTrue()
        ->and($roles['Hospital Administrator']->fresh()->permissions()->where('name', 'restore doctors')->exists())->toBeFalse()
        ->and($roles['Records Clerk']->fresh()->permissions()->where('name', 'restore doctors')->exists())->toBeFalse();
});

it('is idempotent: a second run updates no roles and leaves other roles untouched', function () {
    seedLandlordTenantForDeleteDoctorsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForDeleteDoctorsBackfill();

    $this->artisan('rbac:backfill-delete-doctors')->assertSuccessful();

    $this->artisan('rbac:backfill-delete-doctors')
        ->expectsOutputToContain('Backfilled tenant doctors-backfill-clinic (0 role(s) updated).')
        ->expectsOutputToContain(DELETE_DOCTORS_RECORDS_CLERK_LINE)
        ->assertSuccessful();

    expect(Permission::where('name', 'delete doctors')->count())->toBe(1)
        ->and(roleHoldsDeleteDoctors($roles['Hospital Administrator']))->toBeTrue()
        ->and(roleHoldsDeleteDoctors($roles['Records Clerk']))->toBeFalse()
        ->and(roleHoldsDeleteDoctors($roles['Super Admin']))->toBeFalse()
        ->and(roleHoldsDeleteDoctors($roles['Doctor Deleter']))->toBeTrue()
        ->and($roles['Records Clerk']->fresh()->hasPermissionTo('view doctors'))->toBeTrue();
});

it('backfills the current tenant and forgets its isolated Spatie cache', function () {
    $tenant = seedLandlordTenantForDeleteDoctorsBackfill();
    $tenant->makeCurrent();

    Permission::findOrCreate('view doctors', 'web');
    $admin = Role::findOrCreate('Hospital Administrator', 'web');
    $admin->givePermissionTo('view doctors');
    Role::findOrCreate('Records Clerk', 'web')->givePermissionTo('view doctors');
    Permission::query()->where('name', 'delete doctors')->delete();

    $registrar = app(PermissionRegistrar::class);
    $registrar->cacheKey = config('permission.cache.key');
    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());

    try {
        $this->artisan('rbac:backfill-delete-doctors')
            ->expectsOutputToContain('Backfilled tenant doctors-backfill-clinic (1 role(s) updated).')
            ->expectsOutputToContain(DELETE_DOCTORS_RECORDS_CLERK_LINE)
            ->assertSuccessful();

        expect($registrar->cacheKey)->toBe($tenantCacheKey)
            ->and(Cache::get($tenantCacheKey))->toBeNull()
            ->and(roleHoldsDeleteDoctors($admin))->toBeTrue();
    } finally {
        Tenant::forgetCurrent();
    }
});

it('reports the roles it would update on --dry-run and writes nothing', function () {
    $tenant = seedLandlordTenantForDeleteDoctorsBackfill();
    Tenant::forgetCurrent();

    Permission::findOrCreate('view doctors', 'web');
    $admin = Role::findOrCreate('Hospital Administrator', 'web');
    $admin->givePermissionTo('view doctors');
    $clerk = Role::findOrCreate('Records Clerk', 'web');
    $clerk->givePermissionTo('view doctors');
    Permission::query()->where('name', 'delete doctors')->delete();

    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());
    $permissionCountBefore = Permission::count();
    $pivotCountBefore = $admin->permissions()->count();

    $this->artisan('rbac:backfill-delete-doctors', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant doctors-backfill-clinic: would update 1 role(s) (Hospital Administrator).')
        ->expectsOutputToContain(DELETE_DOCTORS_RECORDS_CLERK_LINE)
        ->doesntExpectOutputToContain('Backfilled tenant')
        ->assertSuccessful();

    expect(Permission::where('name', 'delete doctors')->exists())->toBeFalse()
        ->and(Permission::count())->toBe($permissionCountBefore)
        ->and($admin->fresh()->permissions()->count())->toBe($pivotCountBefore)
        ->and(Cache::get($tenantCacheKey))->toBe(['stale' => true])
        ->and(roleHoldsDeleteDoctors($admin))->toBeFalse()
        ->and(roleHoldsDeleteDoctors($clerk))->toBeFalse();
});

it('safety report lists only non-SA/HA roles lacking delete doctors and never grants', function () {
    seedLandlordTenantForDeleteDoctorsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForDeleteDoctorsBackfill();

    $this->artisan('rbac:backfill-delete-doctors', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant doctors-backfill-clinic: would update 1 role(s) (Hospital Administrator).')
        ->expectsOutputToContain(DELETE_DOCTORS_RECORDS_CLERK_LINE)
        ->doesntExpectOutputToContain('role "Doctor Deleter"')
        ->doesntExpectOutputToContain('role "Super Admin"')
        ->doesntExpectOutputToContain('role "Hospital Administrator"')
        ->assertSuccessful();

    expect(roleHoldsDeleteDoctors($roles['Records Clerk']))->toBeFalse()
        ->and(roleHoldsDeleteDoctors($roles['Hospital Administrator']))->toBeFalse();
});

it('updates zero roles when the tenant has no Hospital Administrator role and still reports Records Clerk', function () {
    seedLandlordTenantForDeleteDoctorsBackfill();
    Tenant::forgetCurrent();
    seedRolesForDeleteDoctorsBackfill(withAdmin: false);

    $this->artisan('rbac:backfill-delete-doctors')
        ->expectsOutputToContain('Backfilled tenant doctors-backfill-clinic (0 role(s) updated).')
        ->expectsOutputToContain(DELETE_DOCTORS_RECORDS_CLERK_LINE)
        ->assertSuccessful();

    expect(Role::where('name', 'Hospital Administrator')->exists())->toBeFalse()
        ->and(roleHoldsDeleteDoctors(Role::findByName('Records Clerk', 'web')))->toBeFalse();
});
