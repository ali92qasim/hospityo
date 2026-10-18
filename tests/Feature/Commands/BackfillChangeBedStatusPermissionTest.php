<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\Tenant;
use App\Support\PermissionRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->artisan('migrate', [
        '--path' => 'database/migrations/tenant',
        '--database' => 'tenant',
    ]);
});

/**
 * Isolated in-memory landlord with one tenant whose connection is the test
 * tenant connection (no switch tasks), so the command's all-tenants path runs
 * against the RefreshDatabase sqlite connection only.
 */
function seedLandlordTenantForChangeBedStatusBackfill(): Tenant
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
        'name' => 'Beds Backfill Clinic',
        'slug' => 'beds-backfill-clinic',
        'domain' => 'beds-backfill-clinic.test',
        'database' => ':memory:',
        'status' => 'active',
    ]);
}

/**
 * Nurse holds view beds (backfill target). Ward Clerk holds view beds only
 * (report-only). Bed Editor already has edit beds. Status Holder already has
 * change bed status. SA/HA hold view beds but are excluded from the report by name.
 *
 * @return array<string, Role>
 */
function seedRolesForChangeBedStatusBackfill(bool $withNurse = true): array
{
    Permission::findOrCreate('view beds', 'web');
    Permission::findOrCreate('edit beds', 'web');

    $roles = [];

    foreach (['Super Admin', 'Hospital Administrator'] as $name) {
        $roles[$name] = Role::findOrCreate($name, 'web');
        $roles[$name]->givePermissionTo('view beds');
    }

    if ($withNurse) {
        $roles['Nurse'] = Role::findOrCreate('Nurse', 'web');
        $roles['Nurse']->givePermissionTo('view beds');
    }

    $roles['Ward Clerk'] = Role::findOrCreate('Ward Clerk', 'web');
    $roles['Ward Clerk']->givePermissionTo('view beds');

    $roles['Bed Editor'] = Role::findOrCreate('Bed Editor', 'web');
    $roles['Bed Editor']->givePermissionTo(['view beds', 'edit beds']);

    Permission::findOrCreate('change bed status', 'web');
    $roles['Status Holder'] = Role::findOrCreate('Status Holder', 'web');
    $roles['Status Holder']->givePermissionTo(['view beds', 'change bed status']);

    return $roles;
}

function roleHoldsChangeBedStatus(Role $role): bool
{
    return $role->fresh()->permissions()->where('name', 'change bed status')->exists();
}

const CHANGE_BED_STATUS_WARD_CLERK_LINE = '[report only] Tenant beds-backfill-clinic: role "Ward Clerk" holds a ward/bed permission but neither edit beds nor change bed status; not granted.';

it('registers change bed status under the ipd beds group', function () {
    expect(PermissionRegistry::forModule('ipd'))->toContain('change bed status')
        ->and(PermissionRegistry::flat())->toContain('change bed status');
});

it('seeds change bed status for Nurse without granting edit beds', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(Permission::where('name', 'change bed status')->where('guard_name', 'web')->exists())->toBeTrue()
        ->and(Role::findByName('Nurse', 'web')->hasPermissionTo('change bed status'))->toBeTrue()
        ->and(Role::findByName('Nurse', 'web')->hasPermissionTo('edit beds'))->toBeFalse();

    foreach (['Receptionist', 'Lab Technician', 'Pharmacist'] as $name) {
        expect(Role::findByName($name, 'web')->hasPermissionTo('change bed status'))
            ->toBeFalse("{$name} must not hold change bed status");
    }
});

it('creates the permission and grants it to Nurse only', function () {
    seedLandlordTenantForChangeBedStatusBackfill();
    Tenant::forgetCurrent();

    Permission::findOrCreate('view beds', 'web');
    $nurse = Role::findOrCreate('Nurse', 'web');
    $nurse->givePermissionTo('view beds');
    $clerk = Role::findOrCreate('Ward Clerk', 'web');
    $clerk->givePermissionTo('view beds');

    Permission::query()->where('name', 'change bed status')->delete();

    expect(Permission::where('name', 'change bed status')->exists())->toBeFalse();

    $this->artisan('rbac:backfill-change-bed-status')
        ->expectsOutputToContain('Backfilled tenant beds-backfill-clinic (1 role(s) updated).')
        ->expectsOutputToContain(CHANGE_BED_STATUS_WARD_CLERK_LINE)
        ->assertSuccessful();

    expect(Permission::where('name', 'change bed status')->where('guard_name', 'web')->exists())->toBeTrue()
        ->and(roleHoldsChangeBedStatus($nurse))->toBeTrue()
        ->and($nurse->fresh()->permissions()->where('name', 'edit beds')->exists())->toBeFalse()
        ->and(roleHoldsChangeBedStatus($clerk))->toBeFalse()
        ->and(Tenant::checkCurrent())->toBeFalse();
});

it('is idempotent: a second run updates no roles and leaves other roles untouched', function () {
    seedLandlordTenantForChangeBedStatusBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForChangeBedStatusBackfill();

    $this->artisan('rbac:backfill-change-bed-status')->assertSuccessful();

    $this->artisan('rbac:backfill-change-bed-status')
        ->expectsOutputToContain('Backfilled tenant beds-backfill-clinic (0 role(s) updated).')
        ->expectsOutputToContain(CHANGE_BED_STATUS_WARD_CLERK_LINE)
        ->assertSuccessful();

    expect(Permission::where('name', 'change bed status')->count())->toBe(1)
        ->and(roleHoldsChangeBedStatus($roles['Nurse']))->toBeTrue()
        ->and(roleHoldsChangeBedStatus($roles['Ward Clerk']))->toBeFalse()
        ->and(roleHoldsChangeBedStatus($roles['Bed Editor']))->toBeFalse()
        ->and(roleHoldsChangeBedStatus($roles['Status Holder']))->toBeTrue()
        ->and($roles['Nurse']->fresh()->hasPermissionTo('edit beds'))->toBeFalse()
        ->and($roles['Ward Clerk']->fresh()->hasPermissionTo('view beds'))->toBeTrue();
});

it('backfills the current tenant and forgets its isolated Spatie cache', function () {
    $tenant = seedLandlordTenantForChangeBedStatusBackfill();
    $tenant->makeCurrent();

    Permission::findOrCreate('view beds', 'web');
    $nurse = Role::findOrCreate('Nurse', 'web');
    $nurse->givePermissionTo('view beds');
    Role::findOrCreate('Ward Clerk', 'web')->givePermissionTo('view beds');
    Permission::query()->where('name', 'change bed status')->delete();

    $registrar = app(PermissionRegistrar::class);
    $registrar->cacheKey = config('permission.cache.key');
    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());

    try {
        $this->artisan('rbac:backfill-change-bed-status')
            ->expectsOutputToContain('Backfilled tenant beds-backfill-clinic (1 role(s) updated).')
            ->expectsOutputToContain(CHANGE_BED_STATUS_WARD_CLERK_LINE)
            ->assertSuccessful();

        expect($registrar->cacheKey)->toBe($tenantCacheKey)
            ->and(Cache::get($tenantCacheKey))->toBeNull()
            ->and(roleHoldsChangeBedStatus($nurse))->toBeTrue()
            ->and($nurse->fresh()->permissions()->where('name', 'edit beds')->exists())->toBeFalse();
    } finally {
        Tenant::forgetCurrent();
    }
});

it('reports the roles it would update on --dry-run and writes nothing', function () {
    $tenant = seedLandlordTenantForChangeBedStatusBackfill();
    Tenant::forgetCurrent();

    Permission::findOrCreate('view beds', 'web');
    $nurse = Role::findOrCreate('Nurse', 'web');
    $nurse->givePermissionTo('view beds');
    $clerk = Role::findOrCreate('Ward Clerk', 'web');
    $clerk->givePermissionTo('view beds');
    Permission::query()->where('name', 'change bed status')->delete();

    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());
    $permissionCountBefore = Permission::count();
    $pivotCountBefore = $nurse->permissions()->count();

    $this->artisan('rbac:backfill-change-bed-status', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant beds-backfill-clinic: would update 1 role(s) (Nurse).')
        ->expectsOutputToContain(CHANGE_BED_STATUS_WARD_CLERK_LINE)
        ->doesntExpectOutputToContain('Backfilled tenant')
        ->assertSuccessful();

    expect(Permission::where('name', 'change bed status')->exists())->toBeFalse()
        ->and(Permission::count())->toBe($permissionCountBefore)
        ->and($nurse->fresh()->permissions()->count())->toBe($pivotCountBefore)
        ->and(Cache::get($tenantCacheKey))->toBe(['stale' => true])
        ->and(roleHoldsChangeBedStatus($nurse))->toBeFalse()
        ->and(roleHoldsChangeBedStatus($clerk))->toBeFalse();
});

it('safety report excludes roles that already hold edit beds or change bed status and never lists SA or HA', function () {
    seedLandlordTenantForChangeBedStatusBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForChangeBedStatusBackfill();

    $this->artisan('rbac:backfill-change-bed-status', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant beds-backfill-clinic: would update 1 role(s) (Nurse).')
        ->expectsOutputToContain(CHANGE_BED_STATUS_WARD_CLERK_LINE)
        ->doesntExpectOutputToContain('role "Bed Editor"')
        ->doesntExpectOutputToContain('role "Status Holder"')
        ->doesntExpectOutputToContain('role "Super Admin"')
        ->doesntExpectOutputToContain('role "Hospital Administrator"')
        ->assertSuccessful();

    expect(roleHoldsChangeBedStatus($roles['Ward Clerk']))->toBeFalse()
        ->and(roleHoldsChangeBedStatus($roles['Bed Editor']))->toBeFalse()
        ->and(roleHoldsChangeBedStatus($roles['Nurse']))->toBeFalse();
});

it('updates zero roles when the tenant has no Nurse role and still reports Ward Clerk', function () {
    seedLandlordTenantForChangeBedStatusBackfill();
    Tenant::forgetCurrent();
    seedRolesForChangeBedStatusBackfill(withNurse: false);

    $this->artisan('rbac:backfill-change-bed-status')
        ->expectsOutputToContain('Backfilled tenant beds-backfill-clinic (0 role(s) updated).')
        ->expectsOutputToContain(CHANGE_BED_STATUS_WARD_CLERK_LINE)
        ->assertSuccessful();

    expect(Role::where('name', 'Nurse')->exists())->toBeFalse()
        ->and(roleHoldsChangeBedStatus(Role::findByName('Ward Clerk', 'web')))->toBeFalse();
});
