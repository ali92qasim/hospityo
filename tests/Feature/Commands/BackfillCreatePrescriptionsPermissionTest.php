<?php

use App\Models\Tenant;
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
function seedLandlordTenantForPrescriptionsBackfill(): Tenant
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
        'name' => 'Prescriptions Backfill Clinic',
        'slug' => 'prescriptions-backfill-clinic',
        'domain' => 'prescriptions-backfill-clinic.test',
        'database' => ':memory:',
        'status' => 'active',
    ]);
}

/**
 * Super Admin and HA hold edit visits + create prescriptions; Doctor and Nurse
 * hold edit visits only; Receptionist has no edit visits; the custom
 * "Ward Clerk" holds edit visits but not create prescriptions.
 *
 * @return array<string, Role>
 */
function seedRolesForPrescriptionsBackfill(bool $withPrescriptionsPermission = true, bool $withNurse = true): array
{
    Permission::findOrCreate('edit visits', 'web');
    Permission::findOrCreate('view visits', 'web');

    $roles = [];

    if ($withPrescriptionsPermission) {
        Permission::findOrCreate('create prescriptions', 'web');

        foreach (['Super Admin', 'Hospital Administrator'] as $name) {
            $roles[$name] = Role::findOrCreate($name, 'web');
            $roles[$name]->givePermissionTo(['edit visits', 'create prescriptions']);
        }
    }

    $editors = $withNurse ? ['Doctor', 'Nurse', 'Ward Clerk'] : ['Doctor', 'Ward Clerk'];
    foreach ($editors as $name) {
        $roles[$name] = Role::findOrCreate($name, 'web');
        $roles[$name]->givePermissionTo('edit visits');
    }

    $roles['Receptionist'] = Role::findOrCreate('Receptionist', 'web');
    $roles['Receptionist']->givePermissionTo('view visits');

    return $roles;
}

function roleHoldsCreatePrescriptions(Role $role): bool
{
    return $role->fresh()->permissions()->where('name', 'create prescriptions')->exists();
}

const PRESCRIPTIONS_BACKFILL_WARD_CLERK_LINE = '[report only] Tenant prescriptions-backfill-clinic: role "Ward Clerk" holds edit visits but not create prescriptions; not granted.';

it('seeds create prescriptions for Doctor and Nurse and keeps it for Pharmacist and HA', function () {
    $this->seed(RolePermissionSeeder::class);

    foreach (['Doctor', 'Nurse', 'Pharmacist', 'Hospital Administrator', 'Super Admin'] as $name) {
        expect(Role::findByName($name, 'web')->hasPermissionTo('create prescriptions'))
            ->toBeTrue("{$name} must hold create prescriptions");
    }

    foreach (['Receptionist', 'Lab Technician'] as $name) {
        expect(Role::findByName($name, 'web')->hasPermissionTo('create prescriptions'))
            ->toBeFalse("{$name} must not hold create prescriptions");
    }

    // The seeder does not create a Medical Records Clerk role; if it ever does, it must not prescribe.
    $clerk = Role::where('name', 'Medical Records Clerk')->where('guard_name', 'web')->first();
    if ($clerk !== null) {
        expect($clerk->hasPermissionTo('create prescriptions'))->toBeFalse();
    }
});

it('grants create prescriptions to exactly Doctor and Nurse', function () {
    seedLandlordTenantForPrescriptionsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForPrescriptionsBackfill();

    $this->artisan('rbac:backfill-create-prescriptions')
        ->expectsOutputToContain('Backfilled tenant prescriptions-backfill-clinic (2 role(s) updated).')
        ->assertSuccessful();

    expect(roleHoldsCreatePrescriptions($roles['Doctor']))->toBeTrue()
        ->and(roleHoldsCreatePrescriptions($roles['Nurse']))->toBeTrue()
        ->and(roleHoldsCreatePrescriptions($roles['Super Admin']))->toBeTrue()
        ->and(roleHoldsCreatePrescriptions($roles['Hospital Administrator']))->toBeTrue()
        ->and(roleHoldsCreatePrescriptions($roles['Receptionist']))->toBeFalse()
        ->and(roleHoldsCreatePrescriptions($roles['Ward Clerk']))->toBeFalse()
        ->and($roles['Receptionist']->fresh()->permissions->pluck('name')->all())->toBe(['view visits'])
        ->and(Tenant::checkCurrent())->toBeFalse();
});

it('is idempotent: a second run updates no roles and leaves Receptionist untouched', function () {
    seedLandlordTenantForPrescriptionsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForPrescriptionsBackfill();

    $this->artisan('rbac:backfill-create-prescriptions')->assertSuccessful();

    $this->artisan('rbac:backfill-create-prescriptions')
        ->expectsOutputToContain('Backfilled tenant prescriptions-backfill-clinic (0 role(s) updated).')
        ->assertSuccessful();

    expect(Permission::where('name', 'create prescriptions')->count())->toBe(1)
        ->and(roleHoldsCreatePrescriptions($roles['Doctor']))->toBeTrue()
        ->and(roleHoldsCreatePrescriptions($roles['Nurse']))->toBeTrue()
        ->and(roleHoldsCreatePrescriptions($roles['Receptionist']))->toBeFalse()
        ->and($roles['Receptionist']->fresh()->permissions->pluck('name')->all())->toBe(['view visits']);
});

it('safety report: names Ward Clerk in dry run and real run, never grants it, and never names admins or targets', function () {
    seedLandlordTenantForPrescriptionsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForPrescriptionsBackfill();

    $wardClerkPermissionCount = $roles['Ward Clerk']->fresh()->permissions()->count();
    $reportPrefix = '[report only] Tenant prescriptions-backfill-clinic: role ';

    foreach ([['--dry-run' => true], []] as $options) {
        $this->artisan('rbac:backfill-create-prescriptions', $options)
            ->expectsOutputToContain(PRESCRIPTIONS_BACKFILL_WARD_CLERK_LINE)
            ->doesntExpectOutputToContain($reportPrefix.'"Super Admin"')
            ->doesntExpectOutputToContain($reportPrefix.'"Hospital Administrator"')
            ->doesntExpectOutputToContain($reportPrefix.'"Doctor"')
            ->doesntExpectOutputToContain($reportPrefix.'"Nurse"')
            ->doesntExpectOutputToContain($reportPrefix.'"Receptionist"')
            ->assertSuccessful();
    }

    expect(roleHoldsCreatePrescriptions($roles['Ward Clerk']))->toBeFalse()
        ->and($roles['Ward Clerk']->fresh()->permissions()->count())->toBe($wardClerkPermissionCount)
        ->and(roleHoldsCreatePrescriptions($roles['Doctor']))->toBeTrue()
        ->and(roleHoldsCreatePrescriptions($roles['Nurse']))->toBeTrue();
});

it('safety report treats every non-target edit visits holder as lacking the permission when it does not exist yet', function () {
    seedLandlordTenantForPrescriptionsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForPrescriptionsBackfill(withPrescriptionsPermission: false);

    $this->artisan('rbac:backfill-create-prescriptions', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant prescriptions-backfill-clinic: would update 2 role(s) (Doctor, Nurse).')
        ->expectsOutputToContain(PRESCRIPTIONS_BACKFILL_WARD_CLERK_LINE)
        ->doesntExpectOutputToContain('role "Doctor"')
        ->doesntExpectOutputToContain('role "Nurse"')
        ->assertSuccessful();

    expect(Permission::where('name', 'create prescriptions')->exists())->toBeFalse()
        ->and($roles['Ward Clerk']->fresh()->permissions()->count())->toBe(1);
});

it('safety report is empty and does not error when edit visits does not exist', function () {
    seedLandlordTenantForPrescriptionsBackfill();
    Tenant::forgetCurrent();
    Permission::findOrCreate('view visits', 'web');
    Role::findOrCreate('Doctor', 'web')->givePermissionTo('view visits');
    Role::findOrCreate('Ward Clerk', 'web')->givePermissionTo('view visits');

    $this->artisan('rbac:backfill-create-prescriptions', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant prescriptions-backfill-clinic: would update 1 role(s) (Doctor).')
        ->doesntExpectOutputToContain('[report only]')
        ->assertSuccessful();
});

it('backfills the current tenant and forgets its isolated Spatie cache', function () {
    $tenant = seedLandlordTenantForPrescriptionsBackfill();
    $tenant->makeCurrent();
    $roles = seedRolesForPrescriptionsBackfill();

    $registrar = app(PermissionRegistrar::class);
    $registrar->cacheKey = config('permission.cache.key');
    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());

    try {
        $this->artisan('rbac:backfill-create-prescriptions')
            ->expectsOutputToContain('Backfilled tenant prescriptions-backfill-clinic (2 role(s) updated).')
            ->expectsOutputToContain(PRESCRIPTIONS_BACKFILL_WARD_CLERK_LINE)
            ->assertSuccessful();

        expect($registrar->cacheKey)->toBe($tenantCacheKey)
            ->and(Cache::get($tenantCacheKey))->toBeNull()
            ->and(roleHoldsCreatePrescriptions($roles['Doctor']))->toBeTrue()
            ->and(roleHoldsCreatePrescriptions($roles['Nurse']))->toBeTrue()
            ->and(roleHoldsCreatePrescriptions($roles['Ward Clerk']))->toBeFalse();
    } finally {
        Tenant::forgetCurrent();
    }
});

it('reports the roles it would update on --dry-run and writes nothing', function () {
    $tenant = seedLandlordTenantForPrescriptionsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForPrescriptionsBackfill();

    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());
    $permissionCountBefore = Permission::count();

    $this->artisan('rbac:backfill-create-prescriptions', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant prescriptions-backfill-clinic: would update 2 role(s) (Doctor, Nurse).')
        ->expectsOutputToContain(PRESCRIPTIONS_BACKFILL_WARD_CLERK_LINE)
        ->doesntExpectOutputToContain('Backfilled tenant')
        ->assertSuccessful();

    expect(Permission::count())->toBe($permissionCountBefore)
        ->and(Cache::get($tenantCacheKey))->toBe(['stale' => true])
        ->and(roleHoldsCreatePrescriptions($roles['Doctor']))->toBeFalse()
        ->and(roleHoldsCreatePrescriptions($roles['Nurse']))->toBeFalse()
        ->and(roleHoldsCreatePrescriptions($roles['Ward Clerk']))->toBeFalse();
});

it('updates only Doctor when the tenant has no Nurse role', function () {
    seedLandlordTenantForPrescriptionsBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForPrescriptionsBackfill(withNurse: false);

    $this->artisan('rbac:backfill-create-prescriptions')
        ->expectsOutputToContain('Backfilled tenant prescriptions-backfill-clinic (1 role(s) updated).')
        ->assertSuccessful();

    expect(Role::where('name', 'Nurse')->exists())->toBeFalse()
        ->and(roleHoldsCreatePrescriptions($roles['Doctor']))->toBeTrue()
        ->and(roleHoldsCreatePrescriptions($roles['Ward Clerk']))->toBeFalse();
});
