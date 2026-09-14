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

it('backfills accounting and pharmacy children onto existing parent plans', function () {
    $plan = Plan::create([
        'slug' => 'has-parents',
        'name' => 'Has Parents',
        'price' => 1,
        'billing_cycle' => 'monthly',
        'modules' => ['accounting', 'pharmacy', 'patients'],
    ]);

    $plan->modules = ModuleRegistry::backfillPharmacyChildren(
        ModuleRegistry::backfillAccountingChildren($plan->modules)
    );
    $plan->save();

    expect($plan->fresh()->modules)->toContain('accounting.profit-loss', 'pharmacy.pos', 'patients')
        ->and($plan->fresh()->modules)->toContain(...ModuleRegistry::ACCOUNTING_CHILD_SLUGS)
        ->and($plan->fresh()->modules)->toContain(...ModuleRegistry::PHARMACY_CHILD_SLUGS);
});
