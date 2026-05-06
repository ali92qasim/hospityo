<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TenantTestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTenantDatabase();
        $this->createTestUser();
    }

    protected function setUpTenantDatabase(): void
    {
        // Make tenant and landlord connections use the same testing SQLite DB
        config([
            'database.connections.tenant' => config('database.connections.testing'),
            'database.connections.landlord' => config('database.connections.testing'),
            'multitenancy.switch_tenant_tasks' => [],
            'permission.testing' => true,
        ]);

        $this->app['db']->purge('tenant');
        $this->app['db']->purge('landlord');

        // Run tenant migrations on the testing connection
        $this->artisan('migrate', [
            '--path' => 'database/migrations/tenant',
            '--database' => 'testing',
        ]);
    }

    protected function createTestUser(): void
    {
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('database.connections.tenant', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('database.connections.landlord', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('multitenancy.switch_tenant_tasks', []);
        $app['config']->set('permission.testing', true);
    }
}
