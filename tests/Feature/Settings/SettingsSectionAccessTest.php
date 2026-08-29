<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);
});

function settingsAccessUser(array $permissions): User
{
    $user = User::create([
        'name' => 'Settings Access User',
        'email' => 'settings-access-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

function validSettingsPayload(): array
{
    return [
        'hospital_name' => 'Access Test Hospital',
        'hospital_address' => '123 Main Street',
        'hospital_phone' => '555-0100',
        'hospital_email' => 'info@access.test',
        'currency' => 'PKR',
        'timezone' => 'Asia/Karachi',
        'date_format' => 'd/m/Y',
        'time_format' => 'h:i A',
    ];
}

it('allows parent settings access to both children and redirects to the first child', function () {
    $this->actingAs(settingsAccessUser(['access settings']));

    $this->get(route('settings.hospital-info'))->assertOk();
    $this->get(route('settings.prescription-print-templates.index'))->assertOk();
    $this->get(route('settings.index'))
        ->assertRedirect(route('settings.hospital-info'));
});

it('allows child-only print access and filters the settings tabs', function () {
    $this->actingAs(settingsAccessUser(['access settings.prescription-print']));

    $response = $this->get(route('settings.prescription-print-templates.index'));

    $response
        ->assertOk()
        ->assertDontSee('Hospital Info')
        ->assertSee('Prescription Print');
    $this->get(route('settings.hospital-info'))->assertForbidden();
    $this->get(route('settings.index'))
        ->assertRedirect(route('settings.prescription-print-templates.index'));
});

it('allows hospital-info-only access and forbids print access', function () {
    $this->actingAs(settingsAccessUser(['access settings.hospital-info']));

    $this->get(route('settings.hospital-info'))->assertOk();
    $this->get(route('settings.prescription-print-templates.index'))->assertForbidden();
});

it('forbids all settings routes without permissions', function () {
    $this->actingAs(settingsAccessUser([]));

    $this->get(route('settings.index'))->assertForbidden();
    $this->get(route('settings.hospital-info'))->assertForbidden();
    $this->get(route('settings.prescription-print-templates.index'))->assertForbidden();
});

it('allows legacy manage settings access to both children', function () {
    $this->actingAs(settingsAccessUser(['manage settings']));

    $this->get(route('settings.hospital-info'))->assertOk();
    $this->get(route('settings.prescription-print-templates.index'))->assertOk();
});

it('allows legacy view settings to read hospital info but not update it', function () {
    $this->actingAs(settingsAccessUser(['view settings']));

    $this->get(route('settings.hospital-info'))->assertOk();
    $this->post(route('settings.update'), validSettingsPayload())->assertForbidden();
});

it('allows legacy edit settings to update hospital info but not read it', function () {
    $this->actingAs(settingsAccessUser(['edit settings']));

    $this->post(route('settings.update'), validSettingsPayload())
        ->assertRedirect(route('settings.index'));
    $this->get(route('settings.hospital-info'))->assertForbidden();
});
