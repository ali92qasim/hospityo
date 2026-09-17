<?php

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\PermissionRegistry;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);

    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => $module === 'rbac'
            || $module === 'hr'
            || str_starts_with($module, 'hr.'));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);
});

function hrRoleFormEditor(): User
{
    foreach (['view roles', 'edit roles'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Role Editor',
        'email' => 'role-editor-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo(['view roles', 'edit roles']);

    return $user;
}

it('renders view hr once on the role form and unchecking it removes the grant everywhere', function () {
    expect(PermissionRegistry::forModule('hr'))->toBe(['view hr'])
        ->and(PermissionRegistry::forModule('hr.employees'))->toContain('view hr')
        ->and(PermissionRegistry::forModule('hr.payroll'))->toContain('view hr');

    Permission::findOrCreate('view hr', 'web');
    Permission::findOrCreate('view employees', 'web');

    $role = Role::create([
        'name' => 'HR Clerk '.uniqid(),
        'guard_name' => 'web',
    ]);
    $role->givePermissionTo(['view hr', 'view employees']);

    $editor = hrRoleFormEditor();

    $html = $this->actingAs($editor)
        ->get(route('roles.edit', $role))
        ->assertOk()
        ->getContent();

    expect(substr_count($html, 'value="view hr"'))->toBe(1);

    $this->actingAs($editor)
        ->put(route('roles.update', $role), [
            'name' => $role->name,
            'permissions' => ['view employees'],
        ])
        ->assertRedirect(route('roles.index'));

    $role->refresh();

    expect($role->hasPermissionTo('view hr'))->toBeFalse()
        ->and($role->hasPermissionTo('view employees'))->toBeTrue();

    $after = $this->actingAs($editor)
        ->get(route('roles.edit', $role))
        ->assertOk()
        ->getContent();

    expect(substr_count($after, 'value="view hr"'))->toBe(1)
        ->and($after)->not->toMatch('/value="view hr"[^>]*\bchecked\b/');
});
