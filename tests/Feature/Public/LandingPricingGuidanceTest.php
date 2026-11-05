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

    $this->withoutVite();
});

function seedLandingPlans(): void
{
    Plan::create([
        'slug' => 'starter',
        'name' => 'Starter',
        'description' => 'For small clinics',
        'price' => 0,
        'billing_cycle' => 'monthly',
        'modules' => ['patients'],
        'is_active' => true,
        'sort_order' => 1,
        'is_popular' => false,
    ]);

    Plan::create([
        'slug' => 'professional',
        'name' => 'Professional',
        'description' => 'For growing hospitals',
        'price' => 49,
        'billing_cycle' => 'monthly',
        'modules' => ['patients', 'laboratory', 'pharmacy'],
        'is_active' => true,
        'sort_order' => 2,
        'is_popular' => true,
    ]);

    Plan::create([
        'slug' => 'enterprise',
        'name' => 'Enterprise',
        'description' => 'For hospital networks',
        'price' => 149,
        'billing_cycle' => 'monthly',
        'modules' => ['patients', 'laboratory', 'pharmacy', 'ot'],
        'is_active' => true,
        'sort_order' => 3,
        'is_popular' => false,
    ]);
}

it('shows most popular only on the plan flagged in the database', function () {
    seedLandingPlans();

    $html = $this->get('/')->assertOk()->getContent();

    expect(substr_count($html, 'data-popular="1"'))->toBe(1)
        ->and($html)->toMatch('/data-plan-slug="professional"[^>]*data-popular="1"/')
        ->and($html)->toContain('Most Popular');
});

it('does not hardcode popular as the second plan when none are flagged', function () {
    seedLandingPlans();
    Plan::query()->update(['is_popular' => false]);

    $html = $this->get('/')->assertOk()->getContent();

    expect(substr_count($html, 'data-popular="1"'))->toBe(0)
        ->and(substr_count($html, 'data-popular="0"'))->toBe(3);
});

it('renders persona chooser guidance on the pricing section', function () {
    seedLandingPlans();

    $this->get('/')
        ->assertOk()
        ->assertSee('What do you run?', false)
        ->assertSee('Laboratory', false)
        ->assertSee('Pharmacy', false)
        ->assertSee('Physiotherapy / clinic', false)
        ->assertSee('Full hospital', false)
        ->assertSee('data-persona="laboratory"', false)
        ->assertSee('data-plan="professional"', false);
});

it('preselects the recommended plan from persona query on registration', function () {
    seedLandingPlans();
    $this->withoutVite();

    $this->get(route('tenant.register', [
        'plan' => 'professional',
        'persona' => 'laboratory',
    ]))
        ->assertOk()
        ->assertSee('Suggested for Laboratory', false)
        ->assertSee('value="professional"', false);
});

it('clears other popular flags when one plan is made popular', function () {
    seedLandingPlans();

    $enterprise = Plan::where('slug', 'enterprise')->firstOrFail();
    $enterprise->update(['is_popular' => true]);
    $enterprise->makeSolePopular();

    expect(Plan::where('slug', 'enterprise')->value('is_popular'))->toBeTrue()
        ->and(Plan::where('slug', 'professional')->value('is_popular'))->toBeFalse();
});
