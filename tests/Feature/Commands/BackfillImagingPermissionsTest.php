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
function seedLandlordTenantForImagingBackfill(): Tenant
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
        'name' => 'Imaging Backfill Clinic',
        'slug' => 'imaging-backfill-clinic',
        'domain' => 'imaging-backfill-clinic.test',
        'database' => ':memory:',
        'status' => 'active',
    ]);
}

function givePermissionsForImagingBackfill(Role $role, array $permissions): Role
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $role->givePermissionTo($permissions);

    return $role;
}

/**
 * Hospital Administrator holds all investigations + both order families.
 * Lab Orders Only holds only lab orders (maps to imaging orders).
 * Catalog Only holds only view investigations (maps to view imaging studies).
 * Radiology Only holds only radiology results (report-only, never granted).
 * Already Migrated already holds imaging permissions (untouched).
 * Super Admin holds radiology results only: excluded from the report by name.
 *
 * @return array<string, Role>
 */
function seedRolesForImagingBackfill(): array
{
    $roles = [];

    $roles['Hospital Administrator'] = givePermissionsForImagingBackfill(Role::findOrCreate('Hospital Administrator', 'web'), [
        'view investigations', 'create investigations', 'edit investigations',
        'view investigation orders', 'create investigation orders', 'edit investigation orders',
        'view lab orders', 'create lab orders', 'edit lab orders',
        'view radiology results',
    ]);
    $roles['Lab Orders Only'] = givePermissionsForImagingBackfill(Role::findOrCreate('Lab Orders Only', 'web'), [
        'view lab orders', 'edit lab orders',
    ]);
    $roles['Catalog Only'] = givePermissionsForImagingBackfill(Role::findOrCreate('Catalog Only', 'web'), [
        'view investigations',
    ]);
    $roles['Radiology Only'] = givePermissionsForImagingBackfill(Role::findOrCreate('Radiology Only', 'web'), [
        'view radiology results', 'create radiology results',
    ]);
    $roles['Already Migrated'] = givePermissionsForImagingBackfill(Role::findOrCreate('Already Migrated', 'web'), [
        'view investigations', 'view radiology results', 'view imaging studies', 'view imaging orders',
    ]);
    $roles['Super Admin'] = givePermissionsForImagingBackfill(Role::findOrCreate('Super Admin', 'web'), [
        'view radiology results',
    ]);

    return $roles;
}

function roleHoldsImagingPermission(Role $role, string $permission): bool
{
    return $role->fresh()->permissions()->where('name', $permission)->exists();
}

/** @return list<string> */
function imagingPermissionsHeldBy(Role $role): array
{
    return $role->fresh()->permissions()
        ->where(fn ($q) => $q->where('name', 'like', '%imaging studies')->orWhere('name', 'like', '%imaging orders'))
        ->orderBy('name')
        ->pluck('name')
        ->all();
}

const IMAGING_BACKFILL_RADIOLOGY_ONLY_LINE = '[report only] Tenant imaging-backfill-clinic: role "Radiology Only" holds a radiology results permission but no imaging studies/orders access; not granted.';

it('maps investigations to imaging studies and investigation or lab orders to imaging orders verb for verb', function () {
    seedLandlordTenantForImagingBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForImagingBackfill();

    $this->artisan('rbac:backfill-imaging-permissions')
        ->expectsOutputToContain('Backfilled tenant imaging-backfill-clinic (3 role(s) updated).')
        ->expectsOutputToContain(IMAGING_BACKFILL_RADIOLOGY_ONLY_LINE)
        ->assertSuccessful();

    expect(imagingPermissionsHeldBy($roles['Hospital Administrator']))->toEqualCanonicalizing([
        'view imaging studies', 'create imaging studies', 'edit imaging studies',
        'view imaging orders', 'create imaging orders', 'edit imaging orders',
    ])
        ->and(imagingPermissionsHeldBy($roles['Lab Orders Only']))->toEqualCanonicalizing([
            'view imaging orders', 'edit imaging orders',
        ])
        ->and(imagingPermissionsHeldBy($roles['Catalog Only']))->toEqualCanonicalizing(['view imaging studies'])
        ->and(Tenant::checkCurrent())->toBeFalse();
});

it('grants imaging orders to a role that holds only investigation orders', function () {
    seedLandlordTenantForImagingBackfill();
    Tenant::forgetCurrent();
    $role = givePermissionsForImagingBackfill(Role::findOrCreate('Inv Orders Only', 'web'), [
        'create investigation orders',
    ]);

    $this->artisan('rbac:backfill-imaging-permissions')->assertSuccessful();

    expect(imagingPermissionsHeldBy($role))->toBe(['create imaging orders']);
});

it('never grants delete radiology results or any radiology permission and keeps existing grants', function () {
    seedLandlordTenantForImagingBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForImagingBackfill();

    $this->artisan('rbac:backfill-imaging-permissions')->assertSuccessful();

    expect(Permission::where('name', 'delete radiology results')->exists())->toBeFalse()
        ->and(roleHoldsImagingPermission($roles['Hospital Administrator'], 'view investigations'))->toBeTrue()
        ->and(roleHoldsImagingPermission($roles['Hospital Administrator'], 'create radiology results'))->toBeFalse()
        ->and(roleHoldsImagingPermission($roles['Radiology Only'], 'view radiology results'))->toBeTrue()
        ->and(imagingPermissionsHeldBy($roles['Radiology Only']))->toBe([])
        ->and(imagingPermissionsHeldBy($roles['Super Admin']))->toBe([]);
});

it('is idempotent: a second run updates no roles', function () {
    seedLandlordTenantForImagingBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForImagingBackfill();

    $this->artisan('rbac:backfill-imaging-permissions')->assertSuccessful();

    $this->artisan('rbac:backfill-imaging-permissions')
        ->expectsOutputToContain('Backfilled tenant imaging-backfill-clinic (0 role(s) updated).')
        ->expectsOutputToContain(IMAGING_BACKFILL_RADIOLOGY_ONLY_LINE)
        ->assertSuccessful();

    expect(Permission::where('name', 'view imaging studies')->count())->toBe(1)
        ->and(imagingPermissionsHeldBy($roles['Hospital Administrator']))->toHaveCount(6)
        ->and(imagingPermissionsHeldBy($roles['Already Migrated']))->toEqualCanonicalizing([
            'view imaging studies', 'view imaging orders',
        ]);
});

it('backfills the current tenant and forgets its isolated Spatie cache', function () {
    $tenant = seedLandlordTenantForImagingBackfill();
    $tenant->makeCurrent();

    $hospitalAdministrator = givePermissionsForImagingBackfill(Role::findOrCreate('Hospital Administrator', 'web'), [
        'view investigations', 'view lab orders',
    ]);

    $registrar = app(PermissionRegistrar::class);
    $registrar->cacheKey = config('permission.cache.key');
    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());

    try {
        $this->artisan('rbac:backfill-imaging-permissions')
            ->expectsOutputToContain('Backfilled tenant imaging-backfill-clinic (1 role(s) updated).')
            ->assertSuccessful();

        expect($registrar->cacheKey)->toBe($tenantCacheKey)
            ->and(Cache::get($tenantCacheKey))->toBeNull()
            ->and(imagingPermissionsHeldBy($hospitalAdministrator))->toEqualCanonicalizing([
                'view imaging studies', 'view imaging orders',
            ]);
    } finally {
        Tenant::forgetCurrent();
    }
});

it('reports the roles it would update on --dry-run and writes nothing', function () {
    $tenant = seedLandlordTenantForImagingBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForImagingBackfill();

    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());
    $permissionCountBefore = Permission::count();
    $pivotCountBefore = $roles['Hospital Administrator']->permissions()->count();

    $this->artisan('rbac:backfill-imaging-permissions', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Tenant imaging-backfill-clinic: would update 3 role(s) (Catalog Only, Hospital Administrator, Lab Orders Only).')
        ->expectsOutputToContain(IMAGING_BACKFILL_RADIOLOGY_ONLY_LINE)
        ->doesntExpectOutputToContain('Backfilled tenant')
        ->assertSuccessful();

    expect(Permission::where('name', 'create imaging studies')->exists())->toBeFalse()
        ->and(Permission::count())->toBe($permissionCountBefore)
        ->and($roles['Hospital Administrator']->fresh()->permissions()->count())->toBe($pivotCountBefore)
        ->and(Cache::get($tenantCacheKey))->toBe(['stale' => true])
        ->and(imagingPermissionsHeldBy($roles['Hospital Administrator']))->toBe([])
        ->and(imagingPermissionsHeldBy($roles['Lab Orders Only']))->toBe([]);
});

it('safety report lists only radiology-only roles and never grants', function () {
    seedLandlordTenantForImagingBackfill();
    Tenant::forgetCurrent();
    $roles = seedRolesForImagingBackfill();

    $this->artisan('rbac:backfill-imaging-permissions', ['--dry-run' => true])
        ->expectsOutputToContain(IMAGING_BACKFILL_RADIOLOGY_ONLY_LINE)
        ->doesntExpectOutputToContain('role "Super Admin"')
        ->doesntExpectOutputToContain('role "Already Migrated"')
        ->doesntExpectOutputToContain('role "Hospital Administrator"')
        ->doesntExpectOutputToContain('role "Catalog Only"')
        ->assertSuccessful();

    $this->artisan('rbac:backfill-imaging-permissions')->assertSuccessful();

    expect(imagingPermissionsHeldBy($roles['Radiology Only']))->toBe([]);
});

it('updates zero roles when no role holds a source permission', function () {
    seedLandlordTenantForImagingBackfill();
    Tenant::forgetCurrent();
    givePermissionsForImagingBackfill(Role::findOrCreate('Nurse', 'web'), ['view patients']);

    $this->artisan('rbac:backfill-imaging-permissions')
        ->expectsOutputToContain('Backfilled tenant imaging-backfill-clinic (0 role(s) updated).')
        ->assertSuccessful();

    expect(Permission::where('name', 'view imaging studies')->exists())->toBeTrue()
        ->and(imagingPermissionsHeldBy(Role::findByName('Nurse', 'web')))->toBe([]);
});
