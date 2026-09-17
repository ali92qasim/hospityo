<?php

use App\Models\ContactMessage;
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
});

function contactPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Ayesha Khan',
        'email' => 'ayesha@example.com',
        'phone' => '03001234567',
        'subject' => 'General Inquiry',
        'message' => 'Please tell me about pricing.',
    ], $overrides);
}

function fakeRecaptchaSuccess(): void
{
    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response([
            'success' => true,
        ], 200),
    ]);
}

function fakeRecaptchaFailure(): void
{
    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ], 200),
    ]);
}

it('shows the recaptcha widget on the contact form', function () {
    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('g-recaptcha', false)
        ->assertSee('test-site-key', false)
        ->assertSee('https://www.google.com/recaptcha/api.js', false);
});

it('rejects a contact submission without a recaptcha token', function () {
    $this->from(route('contact'))
        ->post(route('contact.submit'), contactPayload())
        ->assertRedirect(route('contact'))
        ->assertSessionHasErrors('g-recaptcha-response');

    expect(ContactMessage::count())->toBe(0);
});

it('rejects a contact submission when google reports the token as invalid', function () {
    fakeRecaptchaFailure();

    $this->from(route('contact'))
        ->post(route('contact.submit'), contactPayload([
            'g-recaptcha-response' => 'bot-token',
        ]))
        ->assertRedirect(route('contact'))
        ->assertSessionHasErrors('g-recaptcha-response');

    expect(ContactMessage::count())->toBe(0);
});

it('saves a contact message when recaptcha verification succeeds', function () {
    fakeRecaptchaSuccess();

    $this->from(route('contact'))
        ->post(route('contact.submit'), contactPayload([
            'g-recaptcha-response' => 'human-token',
        ]))
        ->assertRedirect(route('contact'))
        ->assertSessionHas('success')
        ->assertSessionDoesntHaveErrors();

    expect(ContactMessage::count())->toBe(1)
        ->and(ContactMessage::first()->email)->toBe('ayesha@example.com');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://www.google.com/recaptcha/api/siteverify'
            && $request['secret'] === 'test-secret-key'
            && $request['response'] === 'human-token';
    });
});

it('shows the recaptcha widget on hospital registration', function () {
    $this->get(route('tenant.register'))
        ->assertOk()
        ->assertSee('g-recaptcha', false)
        ->assertSee('test-site-key', false);
});

it('rejects hospital registration without a recaptcha token', function () {
    Plan::create([
        'slug' => 'starter',
        'name' => 'Starter',
        'price' => 0,
        'billing_cycle' => 'monthly',
        'trial_days' => 14,
        'modules' => ['patients'],
        'is_active' => true,
    ]);

    $this->from(route('tenant.register'))
        ->post(route('tenant.register.store'), [
            'hospital_name' => 'Spam Clinic',
            'email' => 'clinic@example.com',
            'admin_name' => 'Bot User',
            'admin_email' => 'bot@example.com',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ])
        ->assertRedirect(route('tenant.register'))
        ->assertSessionHasErrors('g-recaptcha-response');
});
