<?php

use App\Models\Plan;

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

it('adds imaging when plan has laboratory but not imaging', function () {
    $plan = Plan::create([
        'slug' => 'legacy-pro',
        'name' => 'Legacy Pro',
        'price' => 49,
        'billing_cycle' => 'monthly',
        'modules' => ['patients', 'doctors', 'departments', 'appointments', 'visits', 'billing', 'laboratory'],
    ]);

    $this->artisan('plans:sync-modules')->assertSuccessful();

    expect($plan->fresh()->modules)->toContain('imaging', 'laboratory');
});

it('adds departments when plan has full starter module set', function () {
    $plan = Plan::create([
        'slug' => 'legacy-starter',
        'name' => 'Legacy Starter',
        'price' => 0,
        'billing_cycle' => 'monthly',
        'modules' => ['patients', 'doctors', 'appointments', 'visits', 'billing'],
    ]);

    $this->artisan('plans:sync-modules')->assertSuccessful();

    expect($plan->fresh()->modules)->toContain('departments');
});

it('preserves custom module selections when merging', function () {
    $plan = Plan::create([
        'slug' => 'custom',
        'name' => 'Custom',
        'price' => 99,
        'billing_cycle' => 'monthly',
        'modules' => ['patients', 'doctors', 'departments', 'appointments', 'visits', 'billing', 'laboratory', 'audit', 'backup'],
    ]);

    $this->artisan('plans:sync-modules')->assertSuccessful();

    expect($plan->fresh()->modules)->toBe([
        'patients',
        'doctors',
        'departments',
        'appointments',
        'visits',
        'billing',
        'laboratory',
        'audit',
        'backup',
        'imaging',
        'emergency',
        'settings',
        'settings.hospital-info',
        'settings.prescription-print',
    ]);
});

it('is idempotent when run multiple times', function () {
    $plan = Plan::create([
        'slug' => 'legacy-starter',
        'name' => 'Legacy Starter',
        'price' => 0,
        'billing_cycle' => 'monthly',
        'modules' => ['patients', 'doctors', 'appointments', 'visits', 'billing', 'laboratory'],
    ]);

    $this->artisan('plans:sync-modules')->assertSuccessful();
    $afterFirst = $plan->fresh()->modules;

    $this->artisan('plans:sync-modules')->assertSuccessful();

    expect($plan->fresh()->modules)->toBe($afterFirst);
});

it('adds emergency when plan has visits but not emergency', function () {
    $plan = Plan::create([
        'slug' => 'opd-only',
        'name' => 'OPD Only',
        'price' => 10,
        'billing_cycle' => 'monthly',
        'modules' => ['visits'],
    ]);

    $this->artisan('plans:sync-modules')->assertSuccessful();

    expect($plan->fresh()->modules)->toContain('emergency', 'visits');
});

it('adds settings and children when a plan has none', function () {
    $plan = Plan::create([
        'slug' => 'no-settings',
        'name' => 'No Settings',
        'price' => 10,
        'billing_cycle' => 'monthly',
        'modules' => ['reports'],
    ]);

    $this->artisan('plans:sync-modules')->assertSuccessful();

    expect($plan->fresh()->modules)->toEqualCanonicalizing([
        'reports',
        'settings',
        'settings.hospital-info',
        'settings.prescription-print',
    ]);
});
