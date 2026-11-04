<?php

use App\Http\Controllers\RoleController;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);
});

function bindRoleSyncTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')->andReturn(true);

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function roleSyncEditor(array $permissions = ['view roles', 'create roles', 'edit roles', 'delete roles']): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Role Sync Editor',
        'email' => 'role-sync-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

it('does not flash success when role permission sync fails on update', function () {
    bindRoleSyncTenant();
    $this->actingAs(roleSyncEditor());

    Permission::findOrCreate('view patients', 'web');

    $role = Role::create([
        'name' => 'Sync Target',
        'guard_name' => 'web',
    ]);

    Route::bind('role', function () use ($role) {
        $failingRole = Mockery::mock($role)->makePartial();
        $failingRole->shouldReceive('syncPermissions')
            ->once()
            ->andThrow(PermissionDoesNotExist::create('1', 'web'));

        return $failingRole;
    });

    $response = $this->from(route('roles.edit', $role))
        ->put(route('roles.update', $role), [
            'name' => 'Sync Target',
            'permissions' => ['view patients'],
        ]);

    $response->assertRedirect(route('roles.edit', $role));
    $response->assertSessionMissing('success');
    $response->assertSessionHasErrors('permissions');
});

it('syncs permission models instead of raw ids on update', function () {
    bindRoleSyncTenant();
    $this->actingAs(roleSyncEditor());

    Permission::findOrCreate('view patients', 'web');
    Permission::findOrCreate('view doctors', 'web');

    $role = Role::create([
        'name' => 'Model Sync Target',
        'guard_name' => 'web',
    ]);

    Route::bind('role', function () use ($role) {
        $observingRole = Mockery::mock($role)->makePartial();
        $observingRole->shouldReceive('syncPermissions')
            ->once()
            ->with(Mockery::on(function ($arg) {
                if (! $arg instanceof Illuminate\Database\Eloquent\Collection) {
                    return false;
                }

                if ($arg->count() !== 2) {
                    return false;
                }

                if (! $arg->every(fn ($permission) => $permission instanceof Permission)) {
                    return false;
                }

                return $arg->pluck('name')->sort()->values()->all()
                    === ['view doctors', 'view patients'];
            }))
            ->andReturnSelf();

        return $observingRole;
    });

    $response = $this->from(route('roles.edit', $role))
        ->put(route('roles.update', $role), [
            'name' => 'Model Sync Target',
            'permissions' => ['view patients', 'view doctors'],
        ]);

    $response->assertRedirect(route('roles.index'));
    $response->assertSessionHas('success');
});

it('rejects unknown permission names without wiping existing permissions', function () {
    bindRoleSyncTenant();
    $this->actingAs(roleSyncEditor());

    $existing = Permission::findOrCreate('view patients', 'web');

    $role = Role::create([
        'name' => 'Unknown Perm Target',
        'guard_name' => 'web',
    ]);
    $role->syncPermissions([$existing]);

    $response = $this->from(route('roles.edit', $role))
        ->put(route('roles.update', $role), [
            'name' => 'Unknown Perm Target',
            'permissions' => ['view patients', 'not a real permission'],
        ]);

    $response->assertRedirect(route('roles.edit', $role));
    $response->assertSessionMissing('success');
    $response->assertSessionHasErrors('permissions');
    expect(session('errors')->first('permissions'))->toContain('not a real permission');

    $role->refresh();
    expect($role->permissions->pluck('name')->all())->toBe(['view patients']);
});

it('allows clearing all permissions when none are posted', function () {
    bindRoleSyncTenant();
    $this->actingAs(roleSyncEditor());

    $existing = Permission::findOrCreate('view patients', 'web');

    $role = Role::create([
        'name' => 'Clear Perms Target',
        'guard_name' => 'web',
    ]);
    $role->syncPermissions([$existing]);

    $response = $this->put(route('roles.update', $role), [
        'name' => 'Clear Perms Target',
        'permissions' => [],
    ]);

    $response->assertRedirect(route('roles.index'));
    $response->assertSessionHas('success');

    $role->refresh();
    expect($role->permissions)->toHaveCount(0);
});

it('raises a permissions validation error when syncPermissionsSafely fails', function () {
    bindRoleSyncTenant();

    Permission::findOrCreate('view patients', 'web');

    $role = Role::create([
        'name' => 'Sync Reflection Target',
        'guard_name' => 'web',
    ]);

    $failingRole = Mockery::mock($role)->makePartial();
    $failingRole->shouldReceive('syncPermissions')
        ->once()
        ->andThrow(PermissionDoesNotExist::create('1', 'web'));

    $controller = app(RoleController::class);
    $method = new ReflectionMethod(RoleController::class, 'syncPermissionsSafely');
    $method->setAccessible(true);

    try {
        $method->invoke($controller, $failingRole, ['view patients']);
        expect(false)->toBeTrue('Expected ValidationException was not thrown');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('permissions')
            ->and($e->errors()['permissions'][0])->toContain('Failed to assign permissions:');
    }
});
