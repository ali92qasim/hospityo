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
        ->assertSee('name="phc_registration_number"', false)
        ->assertSee('PHC Registration Number')
        ->assertSee('optional', false)
        ->assertSee('name="currency"', false)
        ->assertSee('name="timezone"', false)
        ->assertSee('name="date_format"', false)
        ->assertSee('name="time_format"', false)
        ->assertSee('name="hospital_logo"', false);
});

it('places the hospital logo file input in a full-width flex child instead of a nested label', function () {
    $html = $this->get(route('settings.hospital-info'))
        ->assertOk()
        ->getContent();

    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $input = (new DOMXPath($dom))->query('//input[@name="hospital_logo"]')->item(0);

    expect($input)->not->toBeNull();

    $parent = $input->parentNode;
    $parentClass = $parent->attributes?->getNamedItem('class')?->nodeValue ?? '';

    expect($parent->nodeName)->not->toBe('label')
        ->and($parentClass)->toContain('flex-1')
        ->and($parentClass)->toContain('min-w-0');
});

it('does not copy native file-selector button classes onto the hospital logo input', function () {
    $this->get(route('settings.hospital-info'))
        ->assertOk()
        ->assertDontSee('file:bg-medical-blue', false)
        ->assertDontSee('file:rounded-full', false);
});

it('updates hospital information and preserves the existing success redirect', function () {
    $payload = hospitalInfoPayload();

    $this->post(route('settings.update'), $payload)
        ->assertRedirect(route('settings.index'))
        ->assertSessionHas('success', 'Settings updated successfully');

    expect(Setting::get('hospital_name'))->toBe($payload['hospital_name']);
});

it('saves an optional phc registration number with hospital information', function () {
    $payload = hospitalInfoPayload([
        'phc_registration_number' => 'R-98765',
    ]);

    $this->post(route('settings.update'), $payload)
        ->assertRedirect(route('settings.index'))
        ->assertSessionDoesntHaveErrors();

    expect(Setting::get('phc_registration_number'))->toBe('R-98765');
});

it('allows hospital information to be saved without a phc registration number', function () {
    $payload = hospitalInfoPayload();
    unset($payload['phc_registration_number']);

    $this->post(route('settings.update'), $payload)
        ->assertRedirect(route('settings.index'))
        ->assertSessionDoesntHaveErrors();

    expect(Setting::get('phc_registration_number'))->toBeNull();
});

it('clears a previously saved phc registration number when the field is emptied', function () {
    Setting::set('phc_registration_number', 'R-11111');

    $this->post(route('settings.update'), hospitalInfoPayload([
        'phc_registration_number' => '',
    ]))
        ->assertRedirect(route('settings.index'))
        ->assertSessionDoesntHaveErrors();

    expect(Setting::get('phc_registration_number'))->toBeNull();
});

it('rejects a missing hospital name without clearing the existing setting', function () {
    Setting::set('hospital_name', 'Existing Hospital');

    $payload = hospitalInfoPayload();
    unset($payload['hospital_name']);

    $this->post(route('settings.update'), $payload)
        ->assertSessionHasErrors('hospital_name');

    expect(Setting::get('hospital_name'))->toBe('Existing Hospital');
});
