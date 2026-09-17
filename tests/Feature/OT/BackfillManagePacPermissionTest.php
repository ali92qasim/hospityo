<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

function seedLandlordConnectionForPacBackfill(): void
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
}

function seedLandlordTenantForPacBackfill(): Tenant
{
    seedLandlordConnectionForPacBackfill();

    return Tenant::create([
        'name' => 'PAC Backfill Clinic',
        'slug' => 'pac-backfill-clinic',
        'domain' => 'pac-backfill-clinic.test',
        'database' => ':memory:',
        'status' => 'active',
    ]);
}

function withNonTestingEnvironmentForPacBackfill(callable $callback): mixed
{
    $previous = app()->environment();
    app()['env'] = 'production';

    try {
        return $callback();
    } finally {
        app()['env'] = $previous;
    }
}

it('grants manage pac to roles that hold any surgeries crud and skips others', function () {
    foreach ([
        'view surgeries',
        'create surgeries',
        'edit surgeries',
        'delete surgeries',
        'view patients',
        'manage surgical checklists',
    ] as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $withView = \Spatie\Permission\Models\Role::findOrCreate('Ot Clerk', 'web');
    $withView->givePermissionTo('view surgeries');

    $withCreate = \Spatie\Permission\Models\Role::findOrCreate('Ot Scheduler', 'web');
    $withCreate->givePermissionTo('create surgeries');

    $patientsOnly = \Spatie\Permission\Models\Role::findOrCreate('Clerk', 'web');
    $patientsOnly->givePermissionTo('view patients');

    $nurse = \Spatie\Permission\Models\Role::findOrCreate('Nurse', 'web');
    $nurse->givePermissionTo('manage surgical checklists');

    $this->artisan('ot:backfill-pac-permission')
        ->expectsOutputToContain('role(s) updated')
        ->assertSuccessful();

    expect($withView->fresh()->hasPermissionTo('manage pac'))->toBeTrue()
        ->and($withCreate->fresh()->hasPermissionTo('manage pac'))->toBeTrue()
        ->and($patientsOnly->fresh()->hasPermissionTo('manage pac'))->toBeFalse()
        ->and($nurse->fresh()->hasPermissionTo('manage pac'))->toBeFalse()
        ->and($nurse->fresh()->hasPermissionTo('manage surgical checklists'))->toBeTrue();
});

it('does not grant manage pac to a doctor role with no surgeries crud', function () {
    foreach (['view surgeries', 'view patients'] as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $doctor = \Spatie\Permission\Models\Role::findOrCreate('Doctor', 'web');
    $doctor->givePermissionTo('view patients');

    $this->artisan('ot:backfill-pac-permission')->assertSuccessful();

    expect($doctor->fresh()->hasPermissionTo('manage pac'))->toBeFalse();
});

it('is idempotent and does not revoke unrelated permissions', function () {
    foreach (['view surgeries', 'view bills'] as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $role = \Spatie\Permission\Models\Role::findOrCreate('Ops', 'web');
    $role->givePermissionTo(['view surgeries', 'view bills']);

    $this->artisan('ot:backfill-pac-permission')->assertSuccessful();
    $this->artisan('ot:backfill-pac-permission')->assertSuccessful();

    expect($role->fresh()->hasPermissionTo('view bills'))->toBeTrue()
        ->and($role->fresh()->hasPermissionTo('view surgeries'))->toBeTrue()
        ->and($role->fresh()->hasPermissionTo('manage pac'))->toBeTrue();
});

it('scopes and forgets the tenant Spatie cache when a tenant is current', function () {
    $tenant = seedLandlordTenantForPacBackfill();
    $tenant->makeCurrent();

    $registrar = app(PermissionRegistrar::class);
    $registrar->cacheKey = config('permission.cache.key');
    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());

    try {
        $this->artisan('ot:backfill-pac-permission')
            ->expectsOutputToContain('1 tenant')
            ->expectsOutputToContain('role(s) updated')
            ->assertSuccessful();

        expect($registrar->cacheKey)->toBe($tenantCacheKey)
            ->and(Cache::get($tenantCacheKey))->toBeNull();
    } finally {
        Tenant::forgetCurrent();
    }
});

it('isolates Spatie cache then forgets current tenant on the all-tenants path', function () {
    $tenant = seedLandlordTenantForPacBackfill();
    Tenant::forgetCurrent();

    $registrar = app(PermissionRegistrar::class);
    $registrar->cacheKey = config('permission.cache.key');
    $tenantCacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
    Cache::put($tenantCacheKey, ['stale' => true], now()->addHour());

    $this->artisan('ot:backfill-pac-permission')
        ->expectsOutputToContain('1 tenant')
        ->expectsOutputToContain('role(s) updated')
        ->assertSuccessful();

    expect(Tenant::checkCurrent())->toBeFalse()
        ->and($registrar->cacheKey)->toBe($tenantCacheKey)
        ->and(Cache::get($tenantCacheKey))->toBeNull();
});

it('fails when landlord listing throws outside testing', function () {
    Tenant::forgetCurrent();

    withNonTestingEnvironmentForPacBackfill(function () {
        $this->artisan('ot:backfill-pac-permission')
            ->expectsOutputToContain('Unable to list tenants')
            ->assertFailed();
    });
});

it('fails when no tenants exist outside testing', function () {
    seedLandlordConnectionForPacBackfill();
    Tenant::forgetCurrent();

    withNonTestingEnvironmentForPacBackfill(function () {
        $this->artisan('ot:backfill-pac-permission')
            ->expectsOutputToContain('No tenants found')
            ->assertFailed();
    });
});
