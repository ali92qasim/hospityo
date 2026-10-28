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
function seedLandlordTenantForViewAssignedVisitsBackfill(): Tenant
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
        'name' => 'Assigned Visits Backfill Clinic',
        'slug' => 'assigned-visits-backfill-clinic',
        'domain' => 'assigned-visits-backfill-clinic.test',
        'database' => ':memory:',
        'status' => 'active',
    ]);
}

/**
 * Doctor holds view visits (backfill target). Hospital Administrator
 * holds view visits (report-only). Assignment Viewer already holds view
 * assigned visits. SA holds view visits but is excluded from the report by name.
 *
 * @return array<string, Role>
 */
function seedRolesForViewAssignedVisitsBackfill(bool $withDoctor = true): array
{
    Permission::findOrCreate('view visits', 'web');
    Permission::findOrCreate('edit visits', 'web');

    $roles = [];

    $roles['Super Admin'] = Role::findOrCreate('Super Admin', 'web');
    $roles['Super Admin']->givePermissionTo('view visits');

    if ($withDoctor) {
        $roles['Doctor'] = Role::findOrCreate('Doctor', 'web');
        $roles['Doctor']->givePermissionTo('view visits');
    }

    $roles['Hospital Administrator'] = Role::findOrCreate('Hospital Administrator', 'web');
    $roles['Hospital Administrator']->givePermissionTo('view visits');

    Permission::findOrCreate('view assigned visits', 'web');
    $roles['Assignment Viewer'] = Role::findOrCreate('Assignment Viewer', 'web');
    $roles['Assignment Viewer']->givePermissionTo(['view visits', 'view assigned visits']);

    return $roles;
}

function roleHoldsViewAssignedVisits(Role $role): bool
{
    return $role->fresh()->permissions()->where('name', 'view assigned visits')->exists();
}

const VIEW_ASSIGNED_VISITS_HOSPITAL_ADMINISTRATOR_LINE = '[report only] Tenant assigned-visits-backfill-clinic: role "Hospital Administrator" holds a visit permission but not view assigned visits; not granted.';

it('creates the permission and grants it to Doctor only', function () {
    seedLandlordTenantForViewAssignedVisitsBackfill();
    Tenant::forgetCurrent();

    Permission::findOrCreate('view visits', 'web');
    $doctor = Role::findOrCreate('Doctor', 'web');
    $doctor->givePermissionTo('view visits');
    $admin = Role::findOrCreate('Hospital Administrator', 'web');
    $admin->givePermissionTo('view visits');

    Permission::query()->where('name', 'view assigned visits')->delete();

    expect(Permission::where('name', 'view assigned visits')->exists())->toBeFalse();

    $this->artisan('rbac:backfill-view-assigned-visits')
        ->expectsOutputToContain('Backfilled tenant assigned-visits-backfill-clinic (1 role(s) updated).')
        ->expectsOutputToContain(VIEW_ASSIGNED_VISITS_HOSPITAL_ADMINISTRATOR_LINE)
        ->assertSuccessful();

    expect(Permission::where('name', 'view assigned visits')->where('guard_name', 'web')->exists())->toBeTrue()
        ->and(roleHoldsViewAssignedVisits($doctor))->toBeTrue()
        ->and(roleHoldsViewAssignedVisits($admin))->toBeFalse()
        ->and(Tenant::checkCurrent())->toBeFalse();
});

it('grants only view assigned visits and no other visit permission', function () {
    seedLandlordTenantForViewAssignedVisitsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForViewAssignedVisitsBackfill();

    $this->artisan('rbac:backfill-view-assigned-visits')->assertSuccessful();

    expect(roleHoldsViewAssignedVisits($roles['Doctor']))->toBeTrue()
        ->and($roles['Doctor']->fresh()->permissions()->where('name', 'edit visits')->exists())->toBeFalse()
        ->and($roles['Hospital Administrator']->fresh()->permissions()->where('name', 'edit visits')->exists())->toBeFalse();
});

it('is idempotent: a second run updates no roles and leaves other roles untouched', function () {
    seedLandlordTenantForViewAssignedVisitsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForViewAssignedVisitsBackfill();

    $this->artisan('rbac:backfill-view-assigned-visits')->assertSuccessful();

    $this->artisan('rbac:backfill-view-assigned-visits')
        ->expectsOutputToContain('Backfilled tenant assigned-visits-backfill-clinic (0 role(s) updated).')
        ->expectsOutputToContain(VIEW_ASSIGNED_VISITS_HOSPITAL_ADMINISTRATOR_LINE)
        ->assertSuccessful();

    expect(Permission::where('name', 'view assigned visits')->count())->toBe(1)
        ->and(roleHoldsViewAssignedVisits($roles['Doctor']))->toBeTrue()
        ->and(roleHoldsViewAssignedVisits($roles['Hospital Administrator']))->toBeFalse()
        ->and(roleHoldsViewAssignedVisits($roles['Super Admin']))->toBeFalse()
        ->and(roleHoldsViewAssignedVisits($roles['Assignment Viewer']))->toBeTrue()
        ->and($roles['Hospital Administrator']->fresh()->hasPermissionTo('view visits'))->toBeTrue();
});

it('backfills the current tenant and forgets its isolated Spatie cache', function () {
    $tenant = seedLandlordTenantForViewAssignedVisitsBackfill();
    $tenant->makeCurrent();

    Permission::findOrCreate('view visits', 'web');
    $doctor = Role::findOrCreate('Doctor', 'web');
    $doctor->givePermissionTo('view visits');
    Role::findOrCreate('Hospital Administrator', 'web')->givePermissionTo('view visits');
    Permission::query()->where('name', 'view assigned visits')->delete();

    $registrar = app(PermissionRegistrar::class);
    $registrar->cacheKey = config('permission.cache.key');
    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());

    try {
        $this->artisan('rbac:backfill-view-assigned-visits')
            ->expectsOutputToContain('Backfilled tenant assigned-visits-backfill-clinic (1 role(s) updated).')
            ->expectsOutputToContain(VIEW_ASSIGNED_VISITS_HOSPITAL_ADMINISTRATOR_LINE)
            ->assertSuccessful();

        expect($registrar->cacheKey)->toBe($tenantCacheKey)
            ->and(Cache::get($tenantCacheKey))->toBeNull()
            ->and(roleHoldsViewAssignedVisits($doctor))->toBeTrue();
    } finally {
        Tenant::forgetCurrent();
    }
});

it('reports the roles it would update on --dry-run and writes nothing', function () {
    $tenant = seedLandlordTenantForViewAssignedVisitsBackfill();
    Tenant::forgetCurrent();

    Permission::findOrCreate('view visits', 'web');
    $doctor = Role::findOrCreate('Doctor', 'web');
    $doctor->givePermissionTo('view visits');
    $admin = Role::findOrCreate('Hospital Administrator', 'web');
    $admin->givePermissionTo('view visits');
    Permission::query()->where('name', 'view assigned visits')->delete();

    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());
    $permissionCountBefore = Permission::count();
    $pivotCountBefore = $doctor->permissions()->count();

    $this->artisan('rbac:backfill-view-assigned-visits', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant assigned-visits-backfill-clinic: would update 1 role(s) (Doctor).')
        ->expectsOutputToContain(VIEW_ASSIGNED_VISITS_HOSPITAL_ADMINISTRATOR_LINE)
        ->doesntExpectOutputToContain('Backfilled tenant')
        ->assertSuccessful();

    expect(Permission::where('name', 'view assigned visits')->exists())->toBeFalse()
        ->and(Permission::count())->toBe($permissionCountBefore)
        ->and($doctor->fresh()->permissions()->count())->toBe($pivotCountBefore)
        ->and(Cache::get($tenantCacheKey))->toBe(['stale' => true])
        ->and(roleHoldsViewAssignedVisits($doctor))->toBeFalse()
        ->and(roleHoldsViewAssignedVisits($admin))->toBeFalse();
});

it('safety report lists only non-SA/Doctor roles lacking view assigned visits and never grants', function () {
    seedLandlordTenantForViewAssignedVisitsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForViewAssignedVisitsBackfill();

    $this->artisan('rbac:backfill-view-assigned-visits', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant assigned-visits-backfill-clinic: would update 1 role(s) (Doctor).')
        ->expectsOutputToContain(VIEW_ASSIGNED_VISITS_HOSPITAL_ADMINISTRATOR_LINE)
        ->doesntExpectOutputToContain('role "Assignment Viewer"')
        ->doesntExpectOutputToContain('role "Super Admin"')
        ->doesntExpectOutputToContain('role "Doctor"')
        ->assertSuccessful();

    expect(roleHoldsViewAssignedVisits($roles['Hospital Administrator']))->toBeFalse()
        ->and(roleHoldsViewAssignedVisits($roles['Doctor']))->toBeFalse();
});

it('updates zero roles when the tenant has no Doctor role and still reports Hospital Administrator', function () {
    seedLandlordTenantForViewAssignedVisitsBackfill();
    Tenant::forgetCurrent();
    seedRolesForViewAssignedVisitsBackfill(withDoctor: false);

    $this->artisan('rbac:backfill-view-assigned-visits')
        ->expectsOutputToContain('Backfilled tenant assigned-visits-backfill-clinic (0 role(s) updated).')
        ->expectsOutputToContain(VIEW_ASSIGNED_VISITS_HOSPITAL_ADMINISTRATOR_LINE)
        ->assertSuccessful();

    expect(Role::where('name', 'Doctor')->exists())->toBeFalse()
        ->and(roleHoldsViewAssignedVisits(Role::findByName('Hospital Administrator', 'web')))->toBeFalse();
});
