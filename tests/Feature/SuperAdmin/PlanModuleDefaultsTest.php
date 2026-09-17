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

it('includes ot hr settings doctor-share accounting in enterprise plan', function () {
    $this->seed(PlanSeeder::class);
    $enterprise = Plan::where('slug', 'enterprise')->first();
    expect($enterprise->modules)->toContain('ot', 'hr', 'settings.doctor-share', 'accounting', 'imaging')
        ->and($enterprise->modules)->not->toContain('doctor-share');
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

it('includes pharmacy catalog children on professional and accounting children on enterprise', function () {
    $this->seed(PlanSeeder::class);
    $pro = Plan::where('slug', 'professional')->first();
    $enterprise = Plan::where('slug', 'enterprise')->first();

    expect($pro->modules)->toContain('pharmacy', ...\App\Models\ModuleRegistry::PHARMACY_CHILD_SLUGS)
        ->and($enterprise->modules)->toContain('accounting', ...\App\Models\ModuleRegistry::ACCOUNTING_CHILD_SLUGS)
        ->and($enterprise->modules)->toContain(...\App\Models\ModuleRegistry::PHARMACY_CHILD_SLUGS);

    $starter = Plan::where('slug', 'starter')->first();
    expect($starter->modules)->not->toContain('pharmacy')
        ->and($starter->modules)->not->toContain('accounting');
});

it('includes hr children on enterprise and not on professional or starter', function () {
    $this->seed(PlanSeeder::class);
    $enterprise = Plan::where('slug', 'enterprise')->first();
    $professional = Plan::where('slug', 'professional')->first();
    $starter = Plan::where('slug', 'starter')->first();

    expect($enterprise->modules)->toContain(...\App\Models\ModuleRegistry::HR_CHILD_SLUGS)
        ->and($professional->modules)->not->toContain('hr')
        ->and($professional->modules)->not->toContain('hr.payroll')
        ->and($starter->modules)->not->toContain('hr');
});

it('includes ot.pac and other OT children on enterprise and not on professional or starter', function () {
    $this->seed(PlanSeeder::class);
    $enterprise = Plan::where('slug', 'enterprise')->first();
    $professional = Plan::where('slug', 'professional')->first();
    $starter = Plan::where('slug', 'starter')->first();

    expect($enterprise->modules)->toContain(...\App\Models\ModuleRegistry::OT_CHILD_SLUGS)
        ->and($professional->modules)->not->toContain('ot')
        ->and($professional->modules)->not->toContain('ot.pac')
        ->and($starter->modules)->not->toContain('ot')
        ->and($starter->modules)->not->toContain('ot.pac');
});
