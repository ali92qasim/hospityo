<?php

use App\Models\Role;
use App\Models\Tenant;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

function seedLandlordTenantForBackfill(): Tenant
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
        'name' => 'Backfill Clinic',
        'slug' => 'backfill-clinic',
        'domain' => 'backfill-clinic.test',
        'database' => ':memory:',
        'status' => 'active',
    ]);
}

it('grants settings child names to roles that hold parent access and skips others', function () {
    foreach ([
        'access settings',
        'access settings.hospital-info',
        'access settings.prescription-print',
        'view patients',
    ] as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $parentRole = \Spatie\Permission\Models\Role::findOrCreate('Clinic Manager', 'web');
    $parentRole->givePermissionTo('access settings');

    $otherRole = \Spatie\Permission\Models\Role::findOrCreate('Clerk', 'web');
    $otherRole->givePermissionTo('view patients');

    $this->artisan('settings:backfill-section-permissions')->assertSuccessful();

    expect($parentRole->fresh()->hasPermissionTo('access settings.hospital-info'))->toBeTrue()
        ->and($parentRole->fresh()->hasPermissionTo('access settings.prescription-print'))->toBeTrue()
        ->and($otherRole->fresh()->hasPermissionTo('access settings.hospital-info'))->toBeFalse();
});

it('is idempotent and does not revoke extra permissions', function () {
    foreach (['access settings', 'access settings.hospital-info', 'access settings.prescription-print', 'view bills'] as $name) {
        Permission::findOrCreate($name, 'web');
    }
    $role = \Spatie\Permission\Models\Role::findOrCreate('Ops', 'web');
    $role->givePermissionTo(['access settings', 'view bills']);

    $this->artisan('settings:backfill-section-permissions')->assertSuccessful();
    $this->artisan('settings:backfill-section-permissions')->assertSuccessful();

    expect($role->fresh()->hasPermissionTo('view bills'))->toBeTrue()
        ->and($role->fresh()->hasPermissionTo('access settings.prescription-print'))->toBeTrue();
});

it('restores hospital administrator child names after they were stripped', function () {
    $this->seed(RolePermissionSeeder::class);
    $ha = Role::findByName('Hospital Administrator', 'web');

    foreach (['access settings.hospital-info', 'access settings.prescription-print'] as $name) {
        if ($ha->hasPermissionTo($name)) {
            $ha->revokePermissionTo($name);
        }
    }

    $this->artisan('settings:backfill-section-permissions')->assertSuccessful();

    expect($ha->fresh()->hasPermissionTo('access settings.hospital-info'))->toBeTrue()
        ->and($ha->fresh()->hasPermissionTo('access settings.prescription-print'))->toBeTrue();
});

it('scopes and forgets the tenant Spatie cache when a tenant is current', function () {
    $tenant = seedLandlordTenantForBackfill();
    $tenant->makeCurrent();

    $registrar = app(PermissionRegistrar::class);
    $registrar->cacheKey = config('permission.cache.key');
    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());

    try {
        $this->artisan('settings:backfill-section-permissions')->assertSuccessful();

        expect($registrar->cacheKey)->toBe($tenantCacheKey)
            ->and(Cache::get($tenantCacheKey))->toBeNull();
    } finally {
        Tenant::forgetCurrent();
    }
});

it('isolates Spatie cache then forgets current tenant on the all-tenants path', function () {
    $tenant = seedLandlordTenantForBackfill();
    Tenant::forgetCurrent();

    $registrar = app(PermissionRegistrar::class);
    $registrar->cacheKey = config('permission.cache.key');
    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());

    $this->artisan('settings:backfill-section-permissions')->assertSuccessful();

    expect(Tenant::checkCurrent())->toBeFalse()
        ->and($registrar->cacheKey)->toBe($tenantCacheKey)
        ->and(Cache::get($tenantCacheKey))->toBeNull();
});
