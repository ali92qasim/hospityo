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

it('does not render a hospital info checkbox on plan create or edit', function () {
    $plan = Plan::where('slug', 'enterprise')->first();

    $this->actingAs($this->superAdmin, 'super_admin')
        ->get(route('super-admin.plans.edit', $plan))
        ->assertOk()
        ->assertDontSee('name="modules[]" value="settings.hospital-info"', false)
        ->assertSee('data-module-child-of="settings"', false)
        ->assertSee('name="modules[]" value="settings.prescription-print"', false);

    $this->actingAs($this->superAdmin, 'super_admin')
        ->get(route('super-admin.plans.create'))
        ->assertOk()
        ->assertDontSee('name="modules[]" value="settings.hospital-info"', false)
        ->assertSee('data-module-child-of="settings"', false)
        ->assertSee('name="modules[]" value="settings.prescription-print"', false);
});
