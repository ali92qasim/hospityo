<?php

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);
});

function bindAdminTenant(array $modules = ['rbac', 'audit', 'backup']): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function adminPermissionUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Admin Permission User',
        'email' => 'admin-perm-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

it('blocks users index without user permissions', function () {
    bindAdminTenant();
    $this->actingAs(adminPermissionUser(['view roles']));

    $this->get(route('users.index'))
        ->assertForbidden();
});

it('allows users index with view users', function () {
    bindAdminTenant();
    $this->actingAs(adminPermissionUser(['view users']));

    $this->get(route('users.index'))
        ->assertOk();
});

it('blocks audit logs without view audit logs permission', function () {
    bindAdminTenant();
    $this->actingAs(adminPermissionUser(['view users']));

    $this->get(route('audit-logs.index'))
        ->assertForbidden();
});

it('allows audit logs index with view audit logs', function () {
    bindAdminTenant();
    $this->actingAs(adminPermissionUser(['view audit logs']));

    $this->get(route('audit-logs.index'))
        ->assertOk();
});

it('allows audit log show with view audit logs', function () {
    bindAdminTenant();
    $this->actingAs(adminPermissionUser(['view audit logs']));

    $log = AuditLog::create([
        'user_id' => null,
        'event' => 'created',
        'auditable_type' => User::class,
        'auditable_id' => 1,
        'old_values' => null,
        'new_values' => ['name' => 'Test'],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
    ]);

    $this->get(route('audit-logs.show', $log))
        ->assertOk();
});

it('blocks backup index without backup permissions', function () {
    bindAdminTenant();
    $this->actingAs(adminPermissionUser(['view users']));

    $this->get(route('backup.index'))
        ->assertForbidden();
});

it('allows backup index with view backup', function () {
    bindAdminTenant();
    $this->actingAs(adminPermissionUser(['view backup']));

    $this->get(route('backup.index'))
        ->assertOk();
});

it('allows backup index with deprecated manage backup', function () {
    bindAdminTenant();
    $this->actingAs(adminPermissionUser(['manage backup']));

    $this->get(route('backup.index'))
        ->assertOk();
});

it('blocks backup create without create backup permission', function () {
    bindAdminTenant();
    $this->actingAs(adminPermissionUser(['view backup']));

    $this->post(route('backup.create'))
        ->assertForbidden();
});

it('allows backup create with create backup', function () {
    bindAdminTenant();
    $this->actingAs(adminPermissionUser(['create backup']));

    $this->post(route('backup.create'), ['type' => 'database'])
        ->assertRedirect();
});

it('blocks settings index without settings permissions', function () {
    bindAdminTenant();
    $this->actingAs(adminPermissionUser(['view users']));

    $this->get(route('settings.index'))
        ->assertForbidden();
});

it('allows settings index with view settings', function () {
    bindAdminTenant();
    $this->actingAs(adminPermissionUser(['view settings']));

    $this->get(route('settings.index'))
        ->assertRedirect(route('settings.hospital-info'));
});

it('allows settings index with deprecated manage settings', function () {
    bindAdminTenant();
    $this->actingAs(adminPermissionUser(['manage settings']));

    $this->get(route('settings.index'))
        ->assertRedirect(route('settings.hospital-info'));
});

it('blocks settings update without edit settings permission', function () {
    bindAdminTenant();
    $this->actingAs(adminPermissionUser(['view settings']));

    $this->post(route('settings.update'), [
        'hospital_name' => 'Test Hospital',
    ])->assertForbidden();
});

it('allows settings update with edit settings', function () {
    bindAdminTenant();
    $this->actingAs(adminPermissionUser(['edit settings']));

    $this->post(route('settings.update'), [
        'hospital_name' => 'Test Hospital',
        'hospital_address' => '123 Main St',
        'hospital_phone' => '555-0100',
        'hospital_email' => 'info@hospital.test',
        'currency' => 'USD',
        'timezone' => 'UTC',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i',
    ])->assertRedirect(route('settings.index'));
});
