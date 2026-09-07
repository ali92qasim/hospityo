<?php

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
        'email' => 'super-admin-explicit@example.com',
        'password' => bcrypt('password'),
    ]);
});

it('marks reports as explicit-grant and does not auto-check children in plan form js', function () {
    $plan = Plan::where('slug', 'enterprise')->first();

    $this->actingAs($this->superAdmin, 'super_admin')
        ->get(route('super-admin.plans.edit', $plan))
        ->assertOk()
        ->assertSee('data-child-access-requires-explicit-grant="1"', false)
        ->assertSee('data-child-access-requires-explicit-grant="0"', false)
        ->assertSee("const explicit = parentBox.getAttribute('data-child-access-requires-explicit-grant') === '1';", false)
        ->assertSee('data-module-child-of="reports"', false);
});
