<?php

use App\Models\Plan;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);

    $this->app['db']->purge('landlord');

    $this->artisan('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);
});

it('includes ot hr doctor-share accounting in enterprise plan', function () {
    $this->seed(PlanSeeder::class);
    $enterprise = Plan::where('slug', 'enterprise')->first();
    expect($enterprise->modules)->toContain('ot', 'hr', 'doctor-share', 'accounting', 'imaging');
});

it('includes imaging and laboratory in professional plan', function () {
    $this->seed(PlanSeeder::class);
    $pro = Plan::where('slug', 'professional')->first();
    expect($pro->modules)->toContain('laboratory', 'imaging');
});

it('includes departments in starter plan', function () {
    $this->seed(PlanSeeder::class);
    $starter = Plan::where('slug', 'starter')->first();
    expect($starter->modules)->toContain('departments');
});

it('includes emergency and settings children in starter plan', function () {
    $this->seed(PlanSeeder::class);
    $starter = Plan::where('slug', 'starter')->first();
    expect($starter->modules)->toContain(
        'emergency',
        'settings',
        'settings.hospital-info',
        'settings.prescription-print',
    );
});
