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
        'name' => 'Timezone User',
        'email' => 'timezone-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($this->user);

    Cache::flush();
});

it('saves the browser timezone on first detection', function () {
    $this->postJson(route('settings.detect-timezone'), [
        'timezone' => 'Asia/Karachi',
    ])
        ->assertOk()
        ->assertJson([
            'updated' => true,
            'timezone' => 'Asia/Karachi',
        ]);

    expect(Setting::get('timezone'))->toBe('Asia/Karachi')
        ->and(Setting::get('timezone_auto_set'))->toBe('1');
});

it('does not overwrite the timezone after it has been auto-set', function () {
    Setting::set('timezone', 'Asia/Karachi');
    Setting::set('timezone_auto_set', '1');

    $this->postJson(route('settings.detect-timezone'), [
        'timezone' => 'Europe/London',
    ])
        ->assertOk()
        ->assertJson([
            'updated' => false,
            'timezone' => 'Asia/Karachi',
        ]);

    expect(Setting::get('timezone'))->toBe('Asia/Karachi');
});

it('rejects an invalid timezone', function () {
    $this->postJson(route('settings.detect-timezone'), [
        'timezone' => 'Not/AZone',
    ])->assertStatus(422);
});

it('upgrades a 24-hour time format when detecting timezone', function () {
    Setting::set('time_format', 'H:i');

    $this->postJson(route('settings.detect-timezone'), [
        'timezone' => 'Asia/Karachi',
    ])->assertOk();

    expect(Setting::get('time_format'))->toBe('h:i A');
});

it('locks auto-detection when settings are saved manually', function () {
    Permission::findOrCreate('edit settings', 'web');
    $this->user->givePermissionTo('edit settings');

    $this->post(route('settings.update'), [
        'hospital_name' => 'Test Hospital',
        'hospital_address' => '123 Main St',
        'hospital_phone' => '555-0100',
        'hospital_email' => 'info@hospital.test',
        'currency' => 'PKR',
        'timezone' => 'Asia/Karachi',
        'date_format' => 'd/m/Y',
        'time_format' => 'h:i A',
    ])->assertRedirect(route('settings.index'));

    expect(Setting::get('timezone'))->toBe('Asia/Karachi')
        ->and(Setting::get('timezone_auto_set'))->toBe('1');
});

it('formats time in 12-hour by default', function () {
    Setting::set('timezone', 'UTC');

    expect(format_time('2026-08-28 15:00:00'))->toBe('03:00 PM');
});

it('formats datetime using 12-hour time by default', function () {
    Setting::set('timezone', 'UTC');
    Setting::set('date_format', 'd/m/Y');

    expect(format_datetime('2026-08-28 15:00:00'))->toBe('28/08/2026 03:00 PM');
});

it('lists IANA timezones with UTC offsets on the settings page', function () {
    Permission::findOrCreate('view settings', 'web');
    $this->user->givePermissionTo('view settings');

    $this->get(route('settings.hospital-info'))
        ->assertOk()
        ->assertSee('Asia/Karachi (UTC+05:00)', false);
});
