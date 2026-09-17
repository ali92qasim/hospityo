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

it('backfills hr children onto existing parent plans and leaves others alone', function () {
    $withHr = Plan::create([
        'slug' => 'has-hr',
        'name' => 'Has HR',
        'price' => 1,
        'billing_cycle' => 'monthly',
        'modules' => ['hr', 'patients'],
    ]);
    $without = Plan::create([
        'slug' => 'no-hr',
        'name' => 'No HR',
        'price' => 1,
        'billing_cycle' => 'monthly',
        'modules' => ['patients'],
    ]);

    $withHr->modules = ModuleRegistry::backfillHrChildren($withHr->modules);
    $withHr->save();
    $without->modules = ModuleRegistry::backfillHrChildren($without->modules);
    $without->save();

    expect($withHr->fresh()->modules)->toContain(...ModuleRegistry::HR_CHILD_SLUGS)
        ->and($without->fresh()->modules)->toBe(['patients']);
});

it('backfills ot children onto existing parent plans and leaves others alone', function () {
    $withOt = Plan::create([
        'slug' => 'has-ot',
        'name' => 'Has OT',
        'price' => 1,
        'billing_cycle' => 'monthly',
        'modules' => ['ot', 'patients'],
    ]);
    $without = Plan::create([
        'slug' => 'no-ot',
        'name' => 'No OT',
        'price' => 1,
        'billing_cycle' => 'monthly',
        'modules' => ['patients'],
    ]);

    $withOt->modules = ModuleRegistry::backfillOtChildren($withOt->modules);
    $withOt->save();
    $without->modules = ModuleRegistry::backfillOtChildren($without->modules);
    $without->save();

    expect($withOt->fresh()->modules)->toContain(...ModuleRegistry::OT_CHILD_SLUGS)
        ->and($without->fresh()->modules)->toBe(['patients']);
});
