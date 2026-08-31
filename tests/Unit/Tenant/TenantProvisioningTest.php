<?php

use App\Models\Permission;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\TenantProvisioningService;
use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask;

uses(Tests\TestCase::class);

function provisionTestLandlordPath(): string
{
    return database_path('tenants/landlord_provision_test.sqlite');
}

function provisionTestTenantPath(): string
{
    return database_path('tenants/tenant_provision-clinic.sqlite');
}

beforeEach(function () {
    foreach ([provisionTestLandlordPath(), provisionTestTenantPath()] as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }

    $dir = database_path('tenants');
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    touch(provisionTestLandlordPath());

    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => provisionTestLandlordPath(),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'database.connections.tenant' => [
            'driver' => 'sqlite',
            'database' => database_path('.tenant_placeholder'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'multitenancy.switch_tenant_tasks' => [
            SwitchTenantDatabaseTask::class,
        ],
        'permission.testing' => true,
    ]);

    $this->app['db']->purge('landlord');
    $this->app['db']->purge('tenant');

    $this->artisan('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);

    Plan::create([
        'slug' => 'starter',
        'name' => 'Starter',
        'price' => 0,
        'billing_cycle' => 'monthly',
        'trial_days' => 14,
        'modules' => ['patients', 'visits', 'billing'],
        'is_active' => true,
    ]);
});

afterEach(function () {
    Tenant::forgetCurrent();

    foreach ([provisionTestLandlordPath(), provisionTestTenantPath()] as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
});

it('provisions a clinic through database, migrations, and seed without failing', function () {
    $tenant = app(TenantProvisioningService::class)->provision(
        data: [
            'name' => 'Provision Clinic',
            'slug' => 'provision-clinic',
            'email' => 'clinic@provision.test',
            'admin_name' => 'Clinic Admin',
            'admin_email' => 'admin@provision.test',
            'admin_password' => 'password123',
            'plan' => 'starter',
        ],
        async: false,
    );

    expect($tenant->fresh()->status)->toBe('active')
        ->and(TenantUser::where('email', 'admin@provision.test')->where('tenant_id', $tenant->id)->exists())->toBeTrue();

    $tenant->makeCurrent();

    expect(User::where('email', 'admin@provision.test')->first())
        ->not->toBeNull()
        ->and(User::where('email', 'admin@provision.test')->first()->hasRole('Super Admin'))->toBeTrue()
        ->and(Permission::where('name', 'access settings.prescription-print')->exists())->toBeTrue()
        ->and(Schema::connection('tenant')->hasTable('prescription_print_templates'))->toBeTrue()
        ->and(Schema::connection('tenant')->hasTable('prescription_print_fields'))->toBeTrue();
});
