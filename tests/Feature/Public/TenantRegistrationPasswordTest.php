<?php

use App\Models\Plan;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'services.recaptcha.site_key' => 'test-site-key',
        'services.recaptcha.secret_key' => 'test-secret-key',
    ]);

    $this->app['db']->purge('landlord');

    $this->artisan('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);

    Http::preventStrayRequests();

    Plan::create([
        'slug' => 'starter',
        'name' => 'Starter',
        'price' => 0,
        'billing_cycle' => 'monthly',
        'trial_days' => 14,
        'modules' => ['patients'],
        'is_active' => true,
    ]);
});

function registrationPayload(array $overrides = []): array
{
    return array_merge([
        'hospital_name' => 'City General Hospital',
        'email' => 'clinic@example.com',
        'admin_name' => 'Admin User',
        'admin_email' => 'admin@example.com',
        'admin_password' => 'Str0ng!Pass',
        'admin_password_confirmation' => 'Str0ng!Pass',
        'g-recaptcha-response' => 'human-token',
    ], $overrides);
}

function fakeRegistrationRecaptchaSuccess(): void
{
    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response([
            'success' => true,
        ], 200),
    ]);
}

it('rejects a weak numeric-only registration password', function () {
    fakeRegistrationRecaptchaSuccess();

    $this->from(route('tenant.register'))
        ->post(route('tenant.register.store'), registrationPayload([
            'admin_password' => '12345678',
            'admin_password_confirmation' => '12345678',
        ]))
        ->assertRedirect(route('tenant.register'))
        ->assertSessionHasErrors('admin_password');
});

it('rejects a password missing uppercase, number, or special character', function () {
    fakeRegistrationRecaptchaSuccess();

    $this->from(route('tenant.register'))
        ->post(route('tenant.register.store'), registrationPayload([
            'admin_password' => 'password',
            'admin_password_confirmation' => 'password',
        ]))
        ->assertRedirect(route('tenant.register'))
        ->assertSessionHasErrors('admin_password');
});

it('accepts a password with upper, lower, number, and special character', function () {
    fakeRegistrationRecaptchaSuccess();

    $validator = validator(
        [
            'admin_password' => 'Abcd1234!',
            'admin_password_confirmation' => 'Abcd1234!',
        ],
        [
            'admin_password' => (new \App\Http\Requests\RegisterTenantRequest)->rules()['admin_password'],
        ]
    );

    expect($validator->fails())->toBeFalse();
});
