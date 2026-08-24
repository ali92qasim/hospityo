<?php

use App\Models\ModuleRegistry;
use App\Models\Plan;
use App\Models\SuperAdmin;
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

    $this->seed(PlanSeeder::class);

    $this->superAdmin = SuperAdmin::create([
        'name' => 'Super Admin',
        'email' => 'super-admin@example.com',
        'password' => bcrypt('password'),
    ]);
});

it('renders module checkboxes with slug values not numeric indices', function () {
    $plan = Plan::where('slug', 'enterprise')->first();

    $this->actingAs($this->superAdmin, 'super_admin')
        ->get(route('super-admin.plans.edit', $plan))
        ->assertOk()
        ->assertSee('name="modules[]" value="visits"', false)
        ->assertSee('name="modules[]" value="pharmacy"', false)
        ->assertDontSee('name="modules[]" value="0"', false);
});

it('stores module slugs when updating a plan', function () {
    $plan = Plan::where('slug', 'enterprise')->first();
    $modules = ModuleRegistry::all();

    $this->actingAs($this->superAdmin, 'super_admin')
        ->from(route('super-admin.plans.edit', $plan))
        ->put(route('super-admin.plans.update', $plan), [
            'name' => 'Enterprise',
            'description' => $plan->description,
            'price' => 149,
            'billing_cycle' => 'monthly',
            'modules' => $modules,
            'is_active' => 1,
        ])
        ->assertSessionHasNoErrors();

    expect($plan->fresh()->modules)->toEqualCanonicalizing($modules);
});

it('rejects numeric module values on plan update', function () {
    $plan = Plan::where('slug', 'enterprise')->first();

    $this->actingAs($this->superAdmin, 'super_admin')
        ->from(route('super-admin.plans.edit', $plan))
        ->put(route('super-admin.plans.update', $plan), [
            'name' => 'Enterprise',
            'price' => 149,
            'billing_cycle' => 'monthly',
            'modules' => ['0', '1', '2'],
            'is_active' => 1,
        ])
        ->assertRedirect(route('super-admin.plans.edit', $plan))
        ->assertSessionHasErrors('modules.0');
});
