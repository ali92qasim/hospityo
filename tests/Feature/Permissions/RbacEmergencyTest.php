<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    foreach (['users', 'roles', 'permissions'] as $resource) {
        foreach (['view', 'create', 'edit', 'delete'] as $verb) {
            Permission::findOrCreate("{$verb} {$resource}", 'web');
        }
    }
    Permission::findOrCreate('view audit logs', 'web');
    Role::findOrCreate('Super Admin', 'web')->syncPermissions(Permission::all());
});

function rbacUserWith(array $permissions, ?string $roleName = null): User
{
    $role = Role::findOrCreate($roleName ?? 'rbac-'.uniqid(), 'web');
    $role->syncPermissions($permissions);
    $user = User::create([
        'name' => 'RBAC '.$role->name,
        'email' => 'rbac-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $user->assignRole($role);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->fresh();
}

// --- C1 / C2: install wizard is closed once the app is installed ---

dataset('install routes', [
    ['get', 'install.index'],
    ['get', 'install.requirements'],
    ['get', 'install.database'],
    ['post', 'install.database.setup'],
    ['get', 'install.admin'],
    ['post', 'install.admin.setup'],
    ['get', 'install.seed'],
    ['post', 'install.seed.run'],
    ['get', 'install.complete'],
]);

it('returns 404 for every install route once the app is installed', function (string $method, string $route) {
    expect(is_file(storage_path('installed')))->toBeTrue();
    // Spies keep a missing guard from rewriting .env, the installed marker, or the database.
    Artisan::spy();
    File::spy();

    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
        ->{$method}(route($route))
        ->assertNotFound();
})->with('install routes');

it('refuses guest install admin setup on an installed app and creates no Super Admin', function () {
    $email = 'intruder-'.uniqid().'@example.com';

    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
        ->post(route('install.admin.setup'), [
            'name' => 'Intruder',
            'email' => $email,
            'password' => 'intruder-pass-1',
            'password_confirmation' => 'intruder-pass-1',
        ])->assertNotFound();

    expect(User::where('email', $email)->exists())->toBeFalse();
});

it('refuses guest install database setup on an installed app without touching env or migrating', function () {
    // Spies guarantee nothing real is written or migrated even if the guard were missing.
    Artisan::spy();
    File::spy();

    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
        ->post(route('install.database.setup'), [
            'db_connection' => 'sqlite',
            'db_database' => 'attacker.sqlite',
        ])->assertNotFound();

    File::shouldNotHaveReceived('put');
    Artisan::shouldNotHaveReceived('call');
});

it('keeps the install wizard reachable on a fresh, not-yet-installed app', function () {
    $marker = storage_path('installed');
    $backup = $marker.'.test-bak';
    rename($marker, $backup);

    try {
        $this->get(route('install.index'))->assertOk();
    } finally {
        rename($backup, $marker);
    }
});

// --- C3: users are authorized per action, by permission only ---

it('lets a view-only users user list and view users but not create, edit or delete', function () {
    $other = rbacUserWith([]);
    $this->actingAs(rbacUserWith(['view users']));

    $this->get(route('users.index'))->assertOk();
    $this->get(route('users.show', $other))->assertOk();
    $this->get(route('users.create'))->assertForbidden();
    $this->post(route('users.store'), [])->assertForbidden();
    $this->get(route('users.edit', $other))->assertForbidden();
    $this->put(route('users.update', $other), [])->assertForbidden();
    $this->delete(route('users.destroy', $other))->assertForbidden();
    expect(User::whereKey($other->id)->exists())->toBeTrue();
});

it('stops a view-only users user from promoting themselves to Super Admin', function () {
    $me = rbacUserWith(['view users']);
    $this->actingAs($me);

    $this->put(route('users.update', $me), [
        'name' => $me->name,
        'email' => $me->email,
        'roles' => ['Super Admin'],
    ])->assertForbidden();

    expect($me->fresh()->hasRole('Super Admin'))->toBeFalse();
});

it('stops a view-only users user from resetting another user password', function () {
    $victim = rbacUserWith([]);
    $this->actingAs(rbacUserWith(['view users']));

    $this->put(route('users.update', $victim), [
        'name' => $victim->name,
        'email' => $victim->email,
        'password' => 'attacker-pass-1',
        'password_confirmation' => 'attacker-pass-1',
    ])->assertForbidden();

    expect(Hash::check('attacker-pass-1', $victim->fresh()->password))->toBeFalse();
});

it('requires view users for the users data endpoint', function () {
    $this->actingAs(rbacUserWith(['create users']));
    $this->get('/users/data')->assertForbidden();

    $this->actingAs(rbacUserWith(['view users']));
    $this->get('/users/data')->assertOk();
});

it('does not let a role named Hospital Administrator into users without the permission', function () {
    $this->actingAs(rbacUserWith([], 'Hospital Administrator'));

    $this->get(route('users.index'))->assertForbidden();
});

it('still lets an edit users user update a user', function () {
    $other = rbacUserWith([]);
    $this->actingAs(rbacUserWith(['view users', 'edit users']));

    $this->put(route('users.update', $other), [
        'name' => 'Renamed User',
        'email' => $other->email,
    ])->assertRedirect();

    expect($other->fresh()->name)->toBe('Renamed User');
});

// --- C4: roles and permissions are authorized per action ---

it('lets a view-only roles user view roles but not create, edit or delete them', function () {
    $target = Role::findOrCreate('Target Role', 'web');
    $this->actingAs(rbacUserWith(['view roles']));

    $this->get(route('roles.index'))->assertOk();
    $this->get(route('roles.create'))->assertForbidden();
    $this->post(route('roles.store'), ['name' => 'Escalated'])->assertForbidden();
    $this->get(route('roles.edit', $target))->assertForbidden();
    $this->put(route('roles.update', $target), ['name' => 'Target Role'])->assertForbidden();
    $this->delete(route('roles.destroy', $target))->assertForbidden();
    expect(Role::where('name', 'Escalated')->exists())->toBeFalse();
});

it('stops a view-only roles user from granting their own role every permission', function () {
    $me = rbacUserWith(['view roles'], 'Role Viewer');
    $role = Role::findByName('Role Viewer', 'web');
    $this->actingAs($me);

    $this->put(route('roles.update', $role), [
        'name' => 'Role Viewer',
        'permissions' => Permission::pluck('name')->all(),
    ])->assertForbidden();

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    expect($me->fresh()->can('delete users'))->toBeFalse()
        ->and($role->fresh()->permissions->pluck('name')->all())->toBe(['view roles']);
});

it('still lets an edit roles user update a role', function () {
    $target = Role::findOrCreate('Editable Role', 'web');
    $this->actingAs(rbacUserWith(['view roles', 'edit roles']));

    $this->put(route('roles.update', $target), [
        'name' => 'Editable Role',
        'permissions' => ['view users'],
    ])->assertRedirect();

    expect($target->fresh()->permissions->pluck('name')->all())->toBe(['view users']);
});

it('lets a view-only permissions user view permissions but not create, edit or delete them', function () {
    $target = Permission::findOrCreate('target permission', 'web');
    $this->actingAs(rbacUserWith(['view permissions']));

    $this->get(route('permissions.index'))->assertOk();
    $this->get(route('permissions.create'))->assertForbidden();
    $this->post(route('permissions.store'), ['name' => 'escalated permission'])->assertForbidden();
    $this->get(route('permissions.edit', $target))->assertForbidden();
    $this->put(route('permissions.update', $target), ['name' => 'renamed'])->assertForbidden();
    $this->delete(route('permissions.destroy', $target))->assertForbidden();
    expect(Permission::where('name', 'escalated permission')->exists())->toBeFalse()
        ->and(Permission::where('name', 'target permission')->exists())->toBeTrue();
});

// --- Audit logs: permission only, no role-name bypass ---

it('requires view audit logs and ignores the Hospital Administrator role name', function () {
    $this->actingAs(rbacUserWith([], 'Hospital Administrator'));
    $this->get(route('audit-logs.index'))->assertForbidden();

    $this->actingAs(rbacUserWith(['view audit logs']));
    $this->get(route('audit-logs.index'))->assertOk();
});
