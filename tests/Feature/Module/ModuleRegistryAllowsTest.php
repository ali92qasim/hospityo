<?php

use App\Models\ModuleRegistry;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
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
});

function catalogTenant(array $modules): Tenant
{
    $plan = Plan::create([
        'slug' => 'cat-'.uniqid(),
        'name' => 'Catalog Plan',
        'price' => 0,
        'billing_cycle' => 'monthly',
        'modules' => $modules,
        'is_active' => true,
    ]);

    return Tenant::create([
        'name' => 'Catalog Clinic',
        'slug' => 'catalog-'.uniqid(),
        'domain' => uniqid().'.test',
        'database' => ':memory:',
        'status' => 'active',
        'plan_id' => $plan->id,
    ]);
}

it('planAllows matches hasModule for top-level slugs including backup', function () {
    $tenant = catalogTenant(['backup', 'patients']);

    expect(ModuleRegistry::planAllows($tenant, 'backup'))->toBe($tenant->hasModule('backup'))
        ->and(ModuleRegistry::planAllows($tenant, 'patients'))->toBeTrue()
        ->and(ModuleRegistry::planAllows($tenant, 'pharmacy'))->toBeFalse()
        ->and(ModuleRegistry::planAllows(null, 'backup'))->toBeTrue();
});

it('planAllows is true for a tenant with no plan (trial)', function () {
    $tenant = Tenant::create([
        'name' => 'Trial Clinic',
        'slug' => 'trial-'.uniqid(),
        'domain' => uniqid().'.test',
        'database' => ':memory:',
        'status' => 'active',
        'plan_id' => null,
    ]);

    expect($tenant->hasModule('backup'))->toBeTrue()
        ->and(ModuleRegistry::planAllows($tenant, 'backup'))->toBeTrue();
});

it('allows requires a backup spatia permission when a user is present', function () {
    $tenant = catalogTenant(['backup']);
    $user = User::create([
        'name' => 'No Backup',
        'email' => 'nobackup-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    expect(ModuleRegistry::allows($tenant, $user, 'backup'))->toBeFalse();

    Permission::findOrCreate('view backup', 'web');
    $user->givePermissionTo('view backup');

    expect(ModuleRegistry::allows($tenant, $user, 'backup'))->toBeTrue()
        ->and(ModuleRegistry::allows($tenant, null, 'backup'))->toBeTrue();
});
