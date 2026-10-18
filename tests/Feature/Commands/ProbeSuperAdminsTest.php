<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->artisan('migrate', [
        '--path' => 'database/migrations/tenant',
        '--database' => 'tenant',
    ]);
});

function seedLandlordTenantForSaProbe(string $slug = 'sa-probe-clinic'): Tenant
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
        'name' => 'SA Probe Clinic',
        'slug' => $slug,
        'domain' => $slug.'.test',
        'database' => ':memory:',
        'status' => 'active',
    ]);
}

it('lists Super Admin users and exits successfully when coverage exists', function () {
    seedLandlordTenantForSaProbe();
    Tenant::forgetCurrent();
    $this->seed(RolePermissionSeeder::class);

    $sa = User::create([
        'name' => 'Probe SA',
        'email' => 'probe-sa-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $sa->assignRole('Super Admin');

    $permissionCountBefore = \Spatie\Permission\Models\Permission::count();
    $roleCountBefore = Role::count();
    $userCountBefore = User::count();

    $this->artisan('rbac:probe-super-admins')
        ->expectsOutputToContain('Tenant sa-probe-clinic: 1 Super Admin user(s):')
        ->expectsOutputToContain($sa->email)
        ->expectsOutputToContain('All probed tenants have at least one Super Admin user.')
        ->assertSuccessful();

    expect(\Spatie\Permission\Models\Permission::count())->toBe($permissionCountBefore)
        ->and(Role::count())->toBe($roleCountBefore)
        ->and(User::count())->toBe($userCountBefore)
        ->and(Tenant::checkCurrent())->toBeFalse();
});

it('flags a tenant with no Super Admin users and exits non-zero without writing', function () {
    seedLandlordTenantForSaProbe('sa-probe-empty');
    Tenant::forgetCurrent();
    Role::findOrCreate('Super Admin', 'web');
    Role::findOrCreate('Nurse', 'web');

    $nurse = User::create([
        'name' => 'Probe Nurse',
        'email' => 'probe-nurse-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $nurse->assignRole('Nurse');

    $userCountBefore = User::count();
    $roleUserPivotBefore = $nurse->roles()->count();

    $this->artisan('rbac:probe-super-admins')
        ->expectsOutputToContain('[FLAG] Tenant sa-probe-empty: no users hold "Super Admin".')
        ->expectsOutputToContain('FLAG: 1 tenant(s) have no Super Admin users.')
        ->assertFailed();

    expect(User::count())->toBe($userCountBefore)
        ->and($nurse->fresh()->roles()->count())->toBe($roleUserPivotBefore)
        ->and($nurse->fresh()->hasRole('Super Admin'))->toBeFalse()
        ->and(Tenant::checkCurrent())->toBeFalse();
});
