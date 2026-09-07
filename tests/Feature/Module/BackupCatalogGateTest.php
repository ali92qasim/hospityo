<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;

beforeEach(function () {
    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'multitenancy.switch_tenant_tasks' => [],
        'permission.testing' => true,
    ]);

    $this->app['db']->purge('landlord');

    $this->artisan('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);

    $this->plan = Plan::create([
        'slug' => 'starter',
        'name' => 'Starter',
        'price' => 0,
        'billing_cycle' => 'monthly',
        'modules' => ['backup'],
        'is_active' => true,
    ]);

    $tenantKey = 'backup-gate-'.uniqid();
    $this->tenant = Tenant::create([
        'name' => 'Backup Clinic',
        'slug' => $tenantKey,
        'domain' => $tenantKey.'.test',
        'database' => 'tenant_backup_clinic',
        'email' => 'clinic@example.com',
        'status' => 'active',
        'plan_id' => $this->plan->id,
    ]);

    TenantUser::register('admin@clinic.test', $this->tenant->id);

    Subscription::create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'status' => 'active',
        'amount' => 99,
        'currency' => 'PKR',
    ]);

    app()->instance(config('multitenancy.current_tenant_container_key'), $this->tenant);

    $this->user = User::create([
        'name' => 'Clinic Admin',
        'email' => 'admin@clinic.test',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
});

it('shows backup in the sidebar only when plan and spatia backup permission both pass', function () {
    $service = app(\App\Services\SidebarService::class);

    $withoutPerm = $service->build($this->user, $this->tenant);
    expect(collect($withoutPerm)->pluck('id'))->not->toContain('backup');

    \Spatie\Permission\Models\Permission::findOrCreate('view backup', 'web');
    $this->user->givePermissionTo('view backup');
    $this->user->unsetRelation('permissions');
    $this->user->unsetRelation('roles');

    $withPerm = $service->build($this->user, $this->tenant);
    expect(collect($withPerm)->pluck('id'))->toContain('backup');
});
