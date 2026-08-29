<?php

use App\Models\User;
use App\Support\SettingsAccess;
use Spatie\Permission\Models\Permission;

function settingsUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Settings Access User',
        'email' => 'settings-access-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    if ($permissions !== []) {
        $user->givePermissionTo($permissions);
    }

    return $user;
}

it('lets a parent-only role into every registered child without storing child permissions', function () {
    $user = settingsUser(['access settings']);

    expect($user->can('access settings.hospital-info'))->toBeFalse()
        ->and($user->can('access settings.prescription-print'))->toBeFalse()
        ->and(SettingsAccess::canAccessSection($user, 'settings.hospital-info', 'GET'))->toBeTrue()
        ->and(SettingsAccess::canAccessSection($user, 'settings.prescription-print', 'GET'))->toBeTrue()
        ->and(SettingsAccess::canAccessSection($user, 'settings', 'GET'))->toBeTrue();
});

it('lets a child-only role into that tab without the parent', function () {
    $user = settingsUser(['access settings.prescription-print']);

    expect(SettingsAccess::canAccessSection($user, 'settings.prescription-print', 'GET'))->toBeTrue()
        ->and(SettingsAccess::canAccessSection($user, 'settings.hospital-info', 'GET'))->toBeFalse()
        ->and(SettingsAccess::canAccessSection($user, 'settings', 'GET'))->toBeFalse();
});

it('denies a hospital-info-only user the print tab', function () {
    $user = settingsUser(['access settings.hospital-info']);

    expect(SettingsAccess::canAccessSection($user, 'settings.hospital-info', 'GET'))->toBeTrue()
        ->and(SettingsAccess::canAccessSection($user, 'settings.prescription-print', 'GET'))->toBeFalse();
});

it('denies users with no settings permissions', function () {
    $user = settingsUser([]);

    expect(SettingsAccess::canAccessAnySection($user))->toBeFalse()
        ->and(SettingsAccess::canAccessSection($user, 'settings.hospital-info', 'GET'))->toBeFalse();
});

it('treats legacy manage settings as parent access', function () {
    $user = settingsUser(['manage settings']);

    expect(SettingsAccess::canAccessSection($user, 'settings.hospital-info', 'GET'))->toBeTrue()
        ->and(SettingsAccess::canAccessSection($user, 'settings.prescription-print', 'POST'))->toBeTrue();
});

it('treats legacy view settings as parent on GET and edit settings as parent on POST', function () {
    $viewer = settingsUser(['view settings']);
    $editor = settingsUser(['edit settings']);

    expect(SettingsAccess::canAccessSection($viewer, 'settings.hospital-info', 'GET'))->toBeTrue()
        ->and(SettingsAccess::canAccessSection($viewer, 'settings.hospital-info', 'POST'))->toBeFalse()
        ->and(SettingsAccess::canAccessSection($editor, 'settings.hospital-info', 'POST'))->toBeTrue()
        ->and(SettingsAccess::canAccessSection($editor, 'settings.hospital-info', 'GET'))->toBeFalse();
});
