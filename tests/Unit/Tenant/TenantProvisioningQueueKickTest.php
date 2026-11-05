<?php

use App\Jobs\Tenant\CreateTenantDatabase;
use App\Jobs\Tenant\MigrateTenantDatabase;
use App\Jobs\Tenant\SeedTenantData;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(Tests\TestCase::class);

beforeEach(function () {
    $path = database_path('tenants/landlord_queue_kick_test.sqlite');
    if (is_file($path)) {
        @unlink($path);
    }

    $dir = database_path('tenants');
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    touch($path);

    config([
        'queue.default' => 'database',
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => $path,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'database.default' => 'landlord',
    ]);

    DB::purge('landlord');
    DB::reconnect('landlord');

    Schema::connection('landlord')->dropIfExists('tenant_users');
    Schema::connection('landlord')->dropIfExists('tenants');
    Schema::connection('landlord')->dropIfExists('plans');

    Schema::connection('landlord')->create('plans', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->unsignedInteger('trial_days')->default(14);
        $table->json('modules')->nullable();
        $table->boolean('is_active')->default(true);
        $table->unsignedInteger('sort_order')->default(0);
        $table->timestamps();
    });

    Schema::connection('landlord')->create('tenants', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->string('domain')->nullable();
        $table->string('database')->nullable();
        $table->string('email')->nullable();
        $table->string('phone')->nullable();
        $table->string('status')->default('provisioning');
        $table->foreignId('plan_id')->nullable();
        $table->timestamp('trial_ends_at')->nullable();
        $table->json('settings')->nullable();
        $table->timestamps();
    });

    Schema::connection('landlord')->create('tenant_users', function ($table) {
        $table->id();
        $table->string('email');
        $table->unsignedBigInteger('tenant_id');
        $table->timestamps();
    });

    Plan::create([
        'name' => 'Starter',
        'slug' => 'starter',
        'trial_days' => 14,
        'modules' => [],
        'is_active' => true,
        'sort_order' => 1,
    ]);
});

afterEach(function () {
    $path = database_path('tenants/landlord_queue_kick_test.sqlite');
    if (is_file($path)) {
        @unlink($path);
    }
});

it('dispatches async provisioning to the database queue for CLI/worker environments', function () {
    Bus::fake();

    app(TenantProvisioningService::class)->provision([
        'name' => 'Queue Kick Clinic',
        'slug' => 'queue-kick-clinic',
        'email' => 'clinic@queue-kick.test',
        'admin_name' => 'Admin',
        'admin_email' => 'admin@queue-kick.test',
        'admin_password' => 'password123',
    ], async: true);

    expect(Tenant::where('slug', 'queue-kick-clinic')->value('status'))->toBe('provisioning');

    Bus::assertChained([
        CreateTenantDatabase::class,
        MigrateTenantDatabase::class,
        SeedTenantData::class,
    ]);

    Bus::assertDispatched(CreateTenantDatabase::class, function ($job) {
        return $job->connection === 'database';
    });
});
