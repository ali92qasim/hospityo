<?php

use App\Models\Tenant;

uses(Tests\TestCase::class);

it('prefixes mysql tenant database names with TENANT_DB_PREFIX', function () {
    config([
        'database.connections.tenant.driver' => 'mysql',
        'database.tenant_db_prefix' => 'codushco_',
    ]);

    expect(Tenant::databaseNameFor('acme'))->toBe('codushco_acme');
});

it('defaults mysql tenant database names to tenant_ prefix', function () {
    config([
        'database.connections.tenant.driver' => 'mysql',
        'database.tenant_db_prefix' => 'tenant_',
    ]);

    expect(Tenant::databaseNameFor('clinic'))->toBe('tenant_clinic');
});

it('does not apply mysql prefix to sqlite tenant paths', function () {
    config([
        'database.connections.tenant.driver' => 'sqlite',
        'database.tenant_db_prefix' => 'codushco_',
    ]);

    expect(Tenant::databaseNameFor('acme'))
        ->toBe(database_path('tenants/tenant_acme.sqlite'));
});
