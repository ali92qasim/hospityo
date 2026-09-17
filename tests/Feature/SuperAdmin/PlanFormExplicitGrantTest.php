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

it('marks settings as explicit-grant so print is not parent-implied in plan form js', function () {
    $plan = Plan::where('slug', 'enterprise')->first();

    $response = $this->actingAs($this->superAdmin, 'super_admin')
        ->get(route('super-admin.plans.edit', $plan))
        ->assertOk()
        ->assertSee('data-module-parent="settings"', false)
        ->assertSee('data-module-child-of="settings"', false)
        ->assertSee('name="modules[]" value="settings.prescription-print"', false)
        ->assertDontSee('name="modules[]" value="settings.hospital-info"', false);

    expect($response->getContent())->toMatch(
        '/data-module-parent="settings"\s+data-child-access-requires-explicit-grant="1"/'
    );
});

it('marks hr as explicit-grant in the plan form', function () {
    $plan = Plan::where('slug', 'enterprise')->first();

    $this->actingAs($this->superAdmin, 'super_admin')
        ->get(route('super-admin.plans.edit', $plan))
        ->assertOk()
        ->assertSee('data-module-parent="hr"', false)
        ->assertSee('data-module-child-of="hr"', false)
        ->assertSee('name="modules[]" value="hr.payroll"', false);

    expect($this->actingAs($this->superAdmin, 'super_admin')
        ->get(route('super-admin.plans.edit', $plan))
        ->getContent())->toMatch(
            '/data-module-parent="hr"\s+data-child-access-requires-explicit-grant="1"/'
        );
});

it('marks ot as explicit-grant in the plan form', function () {
    $plan = Plan::where('slug', 'enterprise')->first();

    $this->actingAs($this->superAdmin, 'super_admin')
        ->get(route('super-admin.plans.edit', $plan))
        ->assertOk()
        ->assertSee('data-module-parent="ot"', false)
        ->assertSee('data-module-child-of="ot"', false)
        ->assertSee('name="modules[]" value="ot.pac"', false);

    expect($this->actingAs($this->superAdmin, 'super_admin')
        ->get(route('super-admin.plans.edit', $plan))
        ->getContent())->toMatch(
            '/data-module-parent="ot"\s+data-child-access-requires-explicit-grant="1"/'
        );
});

