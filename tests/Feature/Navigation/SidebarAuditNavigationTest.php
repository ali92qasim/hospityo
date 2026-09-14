<?php

use App\Models\Tenant;
use App\Models\User;
use App\Services\SidebarService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->service = new SidebarService;
});

function auditNavUser(array $permissions, ?string $role = null): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Audit Nav User',
        'email' => 'audit-nav-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    if ($permissions !== []) {
        $user->givePermissionTo($permissions);
    }

    if ($role !== null) {
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);
    }

    return $user;
}

function auditNavTenant(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class);
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    return $tenant;
}

it('shows audit logs without the rbac module', function () {
    $user = auditNavUser(['view audit logs']);
    $menu = app(SidebarService::class)->build($user, auditNavTenant(['audit']));

    expect(collect($menu)->firstWhere('id', 'audit'))->not->toBeNull()
        ->and(collect($menu)->firstWhere('id', 'audit')['label'])->toBe('Audit Logs')
        ->and(collect($menu)->firstWhere('id', 'audit')['type'])->toBe('link')
        ->and(collect($menu)->firstWhere('id', 'access'))->toBeNull();
});

it('keeps audit logs out of the access control group when both modules are on', function () {
    $user = auditNavUser(['view audit logs', 'view roles']);
    $menu = app(SidebarService::class)->build($user, auditNavTenant(['audit', 'rbac']));
    $access = collect($menu)->firstWhere('id', 'access');

    expect(collect($menu)->firstWhere('id', 'audit'))->not->toBeNull()
        ->and($access)->not->toBeNull()
        ->and(collect($access['items'])->pluck('label'))->not->toContain('Audit Logs')
        ->and($access['patterns'])->not->toContain('audit-logs.*');
});

it('hides audit logs when the audit module is off even if rbac is on', function () {
    $user = auditNavUser(['view audit logs', 'view roles']);
    $menu = app(SidebarService::class)->build($user, auditNavTenant(['rbac']));

    expect(collect($menu)->firstWhere('id', 'audit'))->toBeNull()
        ->and(collect($menu)->firstWhere('id', 'access'))->not->toBeNull();
});
