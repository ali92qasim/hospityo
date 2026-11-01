<?php

use App\Jobs\Tenant\CreateTenantDatabase;
use App\Models\Tenant;
use App\Services\SharedHosting\CpanelMysqlProvisioner;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;

uses(Tests\TestCase::class);

it('uses the cPanel provisioner when shared hosting is enabled', function () {
    config([
        'shared_hosting.enabled' => true,
        'database.connections.tenant.driver' => 'mysql',
    ]);
    $this->app['env'] = 'production';

    $this->mock(CpanelMysqlProvisioner::class, function (MockInterface $mock) {
        $mock->shouldReceive('create')
            ->once()
            ->with('codushco_acme');
    });

    DB::shouldReceive('connection')->never();

    $tenant = new Tenant([
        'name' => 'Acme',
        'slug' => 'acme',
        'database' => 'codushco_acme',
        'status' => 'provisioning',
    ]);
    $tenant->id = 1;

    (new CreateTenantDatabase($tenant))->handle();
});

it('does not call the cPanel provisioner when shared hosting is disabled', function () {
    config([
        'shared_hosting.enabled' => false,
        'database.connections.tenant.driver' => 'mysql',
    ]);
    $this->app['env'] = 'production';

    $this->mock(CpanelMysqlProvisioner::class, function (MockInterface $mock) {
        $mock->shouldReceive('create')->never();
    });

    DB::shouldReceive('connection')
        ->once()
        ->with('landlord')
        ->andReturnSelf();
    DB::shouldReceive('statement')
        ->once()
        ->with("CREATE DATABASE IF NOT EXISTS `tenant_plain` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $tenant = new Tenant([
        'name' => 'Plain',
        'slug' => 'plain',
        'database' => 'tenant_plain',
        'status' => 'provisioning',
    ]);
    $tenant->id = 2;

    (new CreateTenantDatabase($tenant))->handle();
});
