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
function seedLandlordTenantForViewDoctorAssignmentsBackfill(): Tenant
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
        'name' => 'Doctor Assignments Backfill Clinic',
        'slug' => 'doctor-assignments-backfill-clinic',
        'domain' => 'doctor-assignments-backfill-clinic.test',
        'database' => ':memory:',
        'status' => 'active',
    ]);
}

/**
 * Doctor holds view doctors (backfill target). Hospital Administrator
 * holds view doctors (report-only). Assignment Viewer already holds view\n * doctor assignments. SA holds view doctors but is excluded from the report by name.
 *
 * @return array<string, Role>
 */
function seedRolesForViewDoctorAssignmentsBackfill(bool $withDoctor = true): array
{
    Permission::findOrCreate('view doctors', 'web');
    Permission::findOrCreate('restore doctors', 'web');

    $roles = [];

    $roles['Super Admin'] = Role::findOrCreate('Super Admin', 'web');
    $roles['Super Admin']->givePermissionTo('view doctors');

    if ($withDoctor) {
        $roles['Doctor'] = Role::findOrCreate('Doctor', 'web');
        $roles['Doctor']->givePermissionTo('view doctors');
    }

    $roles['Hospital Administrator'] = Role::findOrCreate('Hospital Administrator', 'web');
    $roles['Hospital Administrator']->givePermissionTo('view doctors');

    Permission::findOrCreate('view doctor assignments', 'web');
    $roles['Assignment Viewer'] = Role::findOrCreate('Assignment Viewer', 'web');
    $roles['Assignment Viewer']->givePermissionTo(['view doctors', 'view doctor assignments']);

    return $roles;
}

function roleHoldsViewDoctorAssignments(Role $role): bool
{
    return $role->fresh()->permissions()->where('name', 'view doctor assignments')->exists();
}

const VIEW_DOCTOR_ASSIGNMENTS_HOSPITAL_ADMINISTRATOR_LINE = '[report only] Tenant doctor-assignments-backfill-clinic: role "Hospital Administrator" holds a doctor permission but not view doctor assignments; not granted.';

it('creates the permission and grants it to Doctor only', function () {
    seedLandlordTenantForViewDoctorAssignmentsBackfill();
    Tenant::forgetCurrent();

    Permission::findOrCreate('view doctors', 'web');
    $doctor = Role::findOrCreate('Doctor', 'web');
    $doctor->givePermissionTo('view doctors');
    $admin = Role::findOrCreate('Hospital Administrator', 'web');
    $admin->givePermissionTo('view doctors');

    Permission::query()->where('name', 'view doctor assignments')->delete();

    expect(Permission::where('name', 'view doctor assignments')->exists())->toBeFalse();

    $this->artisan('rbac:backfill-view-doctor-assignments')
        ->expectsOutputToContain('Backfilled tenant doctor-assignments-backfill-clinic (1 role(s) updated).')
        ->expectsOutputToContain(VIEW_DOCTOR_ASSIGNMENTS_HOSPITAL_ADMINISTRATOR_LINE)
        ->assertSuccessful();

    expect(Permission::where('name', 'view doctor assignments')->where('guard_name', 'web')->exists())->toBeTrue()
        ->and(roleHoldsViewDoctorAssignments($doctor))->toBeTrue()
        ->and(roleHoldsViewDoctorAssignments($admin))->toBeFalse()
        ->and(Tenant::checkCurrent())->toBeFalse();
});

it('does not grant restore doctors', function () {
    seedLandlordTenantForViewDoctorAssignmentsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForViewDoctorAssignmentsBackfill();

    $this->artisan('rbac:backfill-view-doctor-assignments')->assertSuccessful();

    expect(roleHoldsViewDoctorAssignments($roles['Doctor']))->toBeTrue()
        ->and($roles['Doctor']->fresh()->permissions()->where('name', 'restore doctors')->exists())->toBeFalse()
        ->and($roles['Hospital Administrator']->fresh()->permissions()->where('name', 'restore doctors')->exists())->toBeFalse();
});

it('is idempotent: a second run updates no roles and leaves other roles untouched', function () {
    seedLandlordTenantForViewDoctorAssignmentsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForViewDoctorAssignmentsBackfill();

    $this->artisan('rbac:backfill-view-doctor-assignments')->assertSuccessful();

    $this->artisan('rbac:backfill-view-doctor-assignments')
        ->expectsOutputToContain('Backfilled tenant doctor-assignments-backfill-clinic (0 role(s) updated).')
        ->expectsOutputToContain(VIEW_DOCTOR_ASSIGNMENTS_HOSPITAL_ADMINISTRATOR_LINE)
        ->assertSuccessful();

    expect(Permission::where('name', 'view doctor assignments')->count())->toBe(1)
        ->and(roleHoldsViewDoctorAssignments($roles['Doctor']))->toBeTrue()
        ->and(roleHoldsViewDoctorAssignments($roles['Hospital Administrator']))->toBeFalse()
        ->and(roleHoldsViewDoctorAssignments($roles['Super Admin']))->toBeFalse()
        ->and(roleHoldsViewDoctorAssignments($roles['Assignment Viewer']))->toBeTrue()
        ->and($roles['Hospital Administrator']->fresh()->hasPermissionTo('view doctors'))->toBeTrue();
});

it('backfills the current tenant and forgets its isolated Spatie cache', function () {
    $tenant = seedLandlordTenantForViewDoctorAssignmentsBackfill();
    $tenant->makeCurrent();

    Permission::findOrCreate('view doctors', 'web');
    $doctor = Role::findOrCreate('Doctor', 'web');
    $doctor->givePermissionTo('view doctors');
    Role::findOrCreate('Hospital Administrator', 'web')->givePermissionTo('view doctors');
    Permission::query()->where('name', 'view doctor assignments')->delete();

    $registrar = app(PermissionRegistrar::class);
    $registrar->cacheKey = config('permission.cache.key');
    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());

    try {
        $this->artisan('rbac:backfill-view-doctor-assignments')
            ->expectsOutputToContain('Backfilled tenant doctor-assignments-backfill-clinic (1 role(s) updated).')
            ->expectsOutputToContain(VIEW_DOCTOR_ASSIGNMENTS_HOSPITAL_ADMINISTRATOR_LINE)
            ->assertSuccessful();

        expect($registrar->cacheKey)->toBe($tenantCacheKey)
            ->and(Cache::get($tenantCacheKey))->toBeNull()
            ->and(roleHoldsViewDoctorAssignments($doctor))->toBeTrue();
    } finally {
        Tenant::forgetCurrent();
    }
});

it('reports the roles it would update on --dry-run and writes nothing', function () {
    $tenant = seedLandlordTenantForViewDoctorAssignmentsBackfill();
    Tenant::forgetCurrent();

    Permission::findOrCreate('view doctors', 'web');
    $doctor = Role::findOrCreate('Doctor', 'web');
    $doctor->givePermissionTo('view doctors');
    $admin = Role::findOrCreate('Hospital Administrator', 'web');
    $admin->givePermissionTo('view doctors');
    Permission::query()->where('name', 'view doctor assignments')->delete();

    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());
    $permissionCountBefore = Permission::count();
    $pivotCountBefore = $doctor->permissions()->count();

    $this->artisan('rbac:backfill-view-doctor-assignments', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant doctor-assignments-backfill-clinic: would update 1 role(s) (Doctor).')
        ->expectsOutputToContain(VIEW_DOCTOR_ASSIGNMENTS_HOSPITAL_ADMINISTRATOR_LINE)
        ->doesntExpectOutputToContain('Backfilled tenant')
        ->assertSuccessful();

    expect(Permission::where('name', 'view doctor assignments')->exists())->toBeFalse()
        ->and(Permission::count())->toBe($permissionCountBefore)
        ->and($doctor->fresh()->permissions()->count())->toBe($pivotCountBefore)
        ->and(Cache::get($tenantCacheKey))->toBe(['stale' => true])
        ->and(roleHoldsViewDoctorAssignments($doctor))->toBeFalse()
        ->and(roleHoldsViewDoctorAssignments($admin))->toBeFalse();
});

it('safety report lists only non-SA/Doctor roles lacking view doctor assignments and never grants', function () {
    seedLandlordTenantForViewDoctorAssignmentsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForViewDoctorAssignmentsBackfill();

    $this->artisan('rbac:backfill-view-doctor-assignments', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant doctor-assignments-backfill-clinic: would update 1 role(s) (Doctor).')
        ->expectsOutputToContain(VIEW_DOCTOR_ASSIGNMENTS_HOSPITAL_ADMINISTRATOR_LINE)
        ->doesntExpectOutputToContain('role "Assignment Viewer"')
        ->doesntExpectOutputToContain('role "Super Admin"')
        ->doesntExpectOutputToContain('role "Doctor"')
        ->assertSuccessful();

    expect(roleHoldsViewDoctorAssignments($roles['Hospital Administrator']))->toBeFalse()
        ->and(roleHoldsViewDoctorAssignments($roles['Doctor']))->toBeFalse();
});

it('updates zero roles when the tenant has no Doctor role and still reports Hospital Administrator', function () {
    seedLandlordTenantForViewDoctorAssignmentsBackfill();
    Tenant::forgetCurrent();
    seedRolesForViewDoctorAssignmentsBackfill(withDoctor: false);

    $this->artisan('rbac:backfill-view-doctor-assignments')
        ->expectsOutputToContain('Backfilled tenant doctor-assignments-backfill-clinic (0 role(s) updated).')
        ->expectsOutputToContain(VIEW_DOCTOR_ASSIGNMENTS_HOSPITAL_ADMINISTRATOR_LINE)
        ->assertSuccessful();

    expect(Role::where('name', 'Doctor')->exists())->toBeFalse()
        ->and(roleHoldsViewDoctorAssignments(Role::findByName('Hospital Administrator', 'web')))->toBeFalse();
});
