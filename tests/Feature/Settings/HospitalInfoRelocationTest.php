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
    ]);

    $this->user = User::create([
        'name' => 'Hospital Info User',
        'email' => 'hospital-info-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('access settings.hospital-info', 'web');
    $this->user->givePermissionTo('access settings.hospital-info');
    $this->actingAs($this->user);

    Cache::flush();
});

function hospitalInfoPayload(array $overrides = []): array
{
    return array_merge([
        'hospital_name' => 'Relocated Hospital',
        'hospital_address' => '456 Clinic Road',
        'hospital_phone' => '555-0199',
        'hospital_email' => 'info@relocated.test',
        'currency' => 'PKR',
        'timezone' => 'Asia/Karachi',
        'date_format' => 'd/m/Y',
        'time_format' => 'h:i A',
    ], $overrides);
}

it('renders hospital information fields inside the settings shell', function () {
    $this->get(route('settings.hospital-info'))
        ->assertOk()
        ->assertSee('Settings')
        ->assertSee('Hospital Information')
        ->assertSee('name="hospital_name"', false)
        ->assertSee('name="currency"', false)
        ->assertSee('name="timezone"', false)
        ->assertSee('name="date_format"', false)
        ->assertSee('name="time_format"', false)
        ->assertSee('name="hospital_logo"', false);
});

it('updates hospital information and preserves the existing success redirect', function () {
    $payload = hospitalInfoPayload();

    $this->post(route('settings.update'), $payload)
        ->assertRedirect(route('settings.index'))
        ->assertSessionHas('success', 'Settings updated successfully');

    expect(Setting::get('hospital_name'))->toBe($payload['hospital_name']);
});

it('rejects a missing hospital name without clearing the existing setting', function () {
    Setting::set('hospital_name', 'Existing Hospital');

    $payload = hospitalInfoPayload();
    unset($payload['hospital_name']);

    $this->post(route('settings.update'), $payload)
        ->assertSessionHasErrors('hospital_name');

    expect(Setting::get('hospital_name'))->toBe('Existing Hospital');
});
