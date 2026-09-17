<?php

use App\Models\ModuleRegistry;
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

it('leaves a starter settings entitlement unchanged without doctor share', function () {
    $plan = Plan::create([
        'slug' => 'starter',
        'name' => 'Starter',
        'price' => 1,
        'billing_cycle' => 'monthly',
        'modules' => ['patients', 'settings', 'settings.hospital-info'],
    ]);

    $plan->modules = ModuleRegistry::backfillSettingsDoctorShare($plan->modules);
    $plan->save();

    expect($plan->fresh()->modules)->toBe(['patients', 'settings', 'settings.hospital-info']);
});

it('leaves a professional settings entitlement unchanged without doctor share', function () {
    $modules = [
        'patients',
        'settings',
        'settings.hospital-info',
        'billing',
        'pharmacy',
        'laboratory',
        'visits',
    ];

    expect(ModuleRegistry::backfillSettingsDoctorShare($modules))->toBe($modules);
});

it('replaces the legacy doctor share entitlement with its settings child', function () {
    expect(ModuleRegistry::backfillSettingsDoctorShare(['doctor-share', 'settings']))
        ->toBe(['settings', 'settings.doctor-share']);
});

it('keeps the child and strips a leftover legacy doctor share entitlement', function () {
    expect(ModuleRegistry::backfillSettingsDoctorShare([
        'settings.doctor-share',
        'settings',
        'doctor-share',
        'settings.doctor-share',
    ]))->toBe(['settings.doctor-share', 'settings']);
});
