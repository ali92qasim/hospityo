<?php

use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use App\Services\TenantModuleProvisioner;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);

    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'permission.testing' => true,
        'multitenancy.switch_tenant_tasks' => [],
    ]);

    $this->app['db']->purge('landlord');

    $this->artisan('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);

    $starter = Plan::create([
        'slug' => 'starter-prov',
        'name' => 'Starter Prov',
        'price' => 0,
        'billing_cycle' => 'monthly',
        'modules' => ['patients', 'visits', 'billing'],
        'is_active' => true,
    ]);

    $enterprise = Plan::create([
        'slug' => 'enterprise-prov',
        'name' => 'Enterprise Prov',
        'price' => 149,
        'billing_cycle' => 'monthly',
        'modules' => ['patients', 'visits', 'billing', 'backup', 'pharmacy'],
        'is_active' => true,
    ]);

    $this->starter = $starter;
    $this->enterprise = $enterprise;

    $this->tenant = Tenant::create([
        'name' => 'Provision Clinic',
        'slug' => 'provision-clinic',
        'domain' => 'provision-clinic.test',
        'database' => ':memory:',
        'status' => 'active',
        'plan_id' => $starter->id,
    ]);

    $this->tenant->makeCurrent();
});

afterEach(function () {
    Tenant::forgetCurrent();
});

it('seeds default roles when rbac is empty', function () {
    expect(Role::count())->toBe(0);

    $result = app(TenantModuleProvisioner::class)->grant($this->tenant, ['backup']);

    expect($result->seededEmptyCatalog)->toBeTrue()
        ->and(Role::where('name', 'Super Admin')->exists())->toBeTrue()
        ->and(Role::where('name', 'Hospital Administrator')->exists())->toBeTrue()
        ->and(Permission::where('name', 'view backup')->exists())->toBeTrue();
});

it('is additive and idempotent on a second grant', function () {
    $provisioner = app(TenantModuleProvisioner::class);
    $provisioner->grant($this->tenant, ['backup']);

    $hospitalAdmin = Role::where('name', 'Hospital Administrator')->first();
    $hospitalAdmin->givePermissionTo('view backup');
    Permission::findOrCreate('custom extra permission', 'web');
    $hospitalAdmin->givePermissionTo('custom extra permission');

    $permissionCount = Permission::count();
    $haCount = $hospitalAdmin->fresh()->permissions()->count();

    $second = $provisioner->grant($this->tenant, ['backup']);

    expect($second->grantsAdded)->toBe(0)
        ->and($second->permissionsCreated)->toBe(0)
        ->and(Permission::count())->toBe($permissionCount)
        ->and($hospitalAdmin->fresh()->permissions()->pluck('name'))->toContain('custom extra permission')
        ->and($hospitalAdmin->fresh()->permissions()->count())->toBe($haCount);
});

it('does not grant new module permissions on change-plan unless confirmed', function () {
    app(TenantModuleProvisioner::class)->grant($this->tenant, ['patients', 'visits', 'billing']);

    $this->tenant->makeCurrent();
    $hospitalAdmin = Role::where('name', 'Hospital Administrator')->first();
    $backupPerms = \App\Support\PermissionRegistry::forModule('backup');
    $hospitalAdmin->revokePermissionTo($backupPerms);

    $superAdmin = SuperAdmin::create([
        'name' => 'Operator',
        'email' => 'operator-prov@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($superAdmin, 'super_admin')
        ->from(route('super-admin.tenants.show', $this->tenant))
        ->post(route('super-admin.tenants.change-plan', $this->tenant), [
            'plan_id' => $this->enterprise->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('pending_module_grant.modules');

    expect($this->tenant->fresh()->plan_id)->toBe($this->enterprise->id);

    $this->tenant->makeCurrent();
    $hospitalAdmin = Role::where('name', 'Hospital Administrator')->first();

    expect($hospitalAdmin->hasPermissionTo('view backup'))->toBeFalse();
});

it('grants added module permissions when the operator confirms', function () {
    app(TenantModuleProvisioner::class)->grant($this->tenant, ['patients', 'visits', 'billing']);

    $this->tenant->makeCurrent();
    $backupPerms = \App\Support\PermissionRegistry::forModule('backup');
    Role::where('name', 'Hospital Administrator')->first()->revokePermissionTo($backupPerms);
    Role::where('name', 'Super Admin')->first()->revokePermissionTo($backupPerms);

    $superAdmin = SuperAdmin::create([
        'name' => 'Operator',
        'email' => 'operator-grant@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($superAdmin, 'super_admin')
        ->post(route('super-admin.tenants.grant-modules', $this->tenant), [
            'modules' => ['backup'],
        ])
        ->assertRedirect();

    $this->tenant->makeCurrent();
    $hospitalAdmin = Role::where('name', 'Hospital Administrator')->first();
    $super = Role::where('name', 'Super Admin')->first();

    expect($hospitalAdmin->hasPermissionTo('view backup'))->toBeTrue()
        ->and($super->hasPermissionTo('view backup'))->toBeTrue();
});

it('auto-provisions empty rbac on change-plan', function () {
    expect(Role::count())->toBe(0);

    $superAdmin = SuperAdmin::create([
        'name' => 'Operator',
        'email' => 'operator-empty@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($superAdmin, 'super_admin')
        ->post(route('super-admin.tenants.change-plan', $this->tenant), [
            'plan_id' => $this->enterprise->id,
        ])
        ->assertRedirect();

    $this->tenant->makeCurrent();

    expect(Role::where('name', 'Super Admin')->exists())->toBeTrue()
        ->and(Permission::where('name', 'view backup')->exists())->toBeTrue();
});

it('dry-run command does not write permissions', function () {
    expect(Permission::count())->toBe(0);

    $this->artisan('tenants:provision-modules', [
        '--tenant' => 'provision-clinic',
    ])->assertSuccessful();

    expect(Permission::count())->toBe(0)
        ->and(Role::count())->toBe(0);
});

it('does not grant report child permissions when only the reports module is granted', function () {
    $provisioner = app(\App\Services\TenantModuleProvisioner::class);
    $provisioner->grant($this->tenant, ['reports']);

    $this->tenant->makeCurrent();
    $ha = \App\Models\Role::where('name', 'Hospital Administrator')->first();

    expect(\App\Support\PermissionRegistry::forModule('reports'))->toBe(['view reports'])
        ->and($ha->hasPermissionTo('view reports'))->toBeTrue()
        ->and($ha->hasPermissionTo('view reports.daily-cash-register'))->toBeFalse()
        ->and(\App\Models\Permission::where('name', 'view reports.daily-cash-register')->exists())->toBeTrue();
});

it('grants a report child only when that slug is in the grant list', function () {
    app(\App\Services\TenantModuleProvisioner::class)->grant(
        $this->tenant,
        ['reports', 'reports.daily-cash-register'],
    );

    $this->tenant->makeCurrent();
    $ha = \App\Models\Role::where('name', 'Hospital Administrator')->first();
    $super = \App\Models\Role::where('name', 'Super Admin')->first();

    expect($ha->hasPermissionTo('view reports.daily-cash-register'))->toBeTrue()
        ->and($super->hasPermissionTo('view reports.daily-cash-register'))->toBeTrue()
        ->and($ha->hasPermissionTo('view reports.revenue'))->toBeFalse();
});
