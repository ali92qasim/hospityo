<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
        \App\Http\Middleware\EnsureSessionNotIdle::class,
    ]);

    config([
        'security.idle_timeout_minutes' => 30,
        'security.idle_timeout_min' => 5,
        'security.idle_timeout_max' => 60,
    ]);

    $this->user = User::create([
        'name' => 'Idle Settings User',
        'email' => 'idle-settings-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('access settings.hospital-info', 'web');
    $this->user->givePermissionTo('access settings.hospital-info');
    $this->actingAs($this->user);

    Cache::flush();
});

function idleSettingsPayload(array $overrides = []): array
{
    return array_merge([
        'hospital_name' => 'Idle Clinic',
        'hospital_address' => '1 Timeout Lane',
        'hospital_phone' => '555-0100',
        'hospital_email' => 'idle@clinic.test',
        'currency' => 'PKR',
        'timezone' => 'Asia/Karachi',
        'date_format' => 'd/m/Y',
        'time_format' => 'h:i A',
        'session_idle_timeout_minutes' => 15,
    ], $overrides);
}

it('shows the session idle timeout field on hospital information', function () {
    $this->get(route('settings.hospital-info'))
        ->assertOk()
        ->assertSee('name="session_idle_timeout_minutes"', false)
        ->assertSee('Auto sign-out after inactivity');
});

it('persists a valid session idle timeout override', function () {
    $this->post(route('settings.update'), idleSettingsPayload([
        'session_idle_timeout_minutes' => 20,
    ]))->assertRedirect();

    expect(Setting::get('session_idle_timeout_minutes'))->toBe('20');
});

it('rejects an idle timeout outside the allowed range', function () {
    $this->from(route('settings.hospital-info'))
        ->post(route('settings.update'), idleSettingsPayload([
            'session_idle_timeout_minutes' => 90,
        ]))
        ->assertRedirect(route('settings.hospital-info'))
        ->assertSessionHasErrors('session_idle_timeout_minutes');
});
