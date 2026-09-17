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

it('does not let a parent-only role into child sections', function () {
    $user = settingsUser(['access settings']);

    expect($user->can('access settings.hospital-info'))->toBeFalse()
        ->and($user->can('access settings.prescription-print'))->toBeFalse()
        ->and(SettingsAccess::canAccessSection($user, 'settings.hospital-info', 'GET'))->toBeFalse()
        ->and(SettingsAccess::canAccessSection($user, 'settings.prescription-print', 'GET'))->toBeFalse()
        ->and(SettingsAccess::canAccessSection($user, 'settings', 'GET'))->toBeFalse()
        ->and(SettingsAccess::canAccessAnySection($user))->toBeFalse();
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

it('allows share rules but not share reports into unbundled doctor share settings', function () {
    $rulesUser = settingsUser(['view share rules']);
    $reportsUser = settingsUser(['view share reports']);

    expect(SettingsAccess::canAccessSection($rulesUser, 'settings.doctor-share', 'GET'))->toBeTrue()
        ->and(SettingsAccess::canAccessSection($reportsUser, 'settings.doctor-share', 'GET'))->toBeFalse();
});

it('denies users with no settings permissions', function () {
    $user = settingsUser([]);

    expect(SettingsAccess::canAccessAnySection($user))->toBeFalse()
        ->and(SettingsAccess::canAccessSection($user, 'settings.hospital-info', 'GET'))->toBeFalse();
});

it('does not let manage settings open child sections', function () {
    $user = settingsUser(['manage settings']);

    expect(SettingsAccess::canAccessSection($user, 'settings.hospital-info', 'GET'))->toBeFalse()
        ->and(SettingsAccess::canAccessSection($user, 'settings.prescription-print', 'POST'))->toBeFalse()
        ->and(SettingsAccess::canAccessAnySection($user))->toBeFalse();
});

it('treats legacy view settings as hospital-info GET and edit settings as hospital-info POST', function () {
    $viewer = settingsUser(['view settings']);
    $editor = settingsUser(['edit settings']);

    expect(SettingsAccess::canAccessSection($viewer, 'settings.hospital-info', 'GET'))->toBeTrue()
        ->and(SettingsAccess::canAccessSection($viewer, 'settings.hospital-info', 'POST'))->toBeFalse()
        ->and(SettingsAccess::canAccessSection($viewer, 'settings.prescription-print', 'GET'))->toBeFalse()
        ->and(SettingsAccess::canAccessSection($editor, 'settings.hospital-info', 'POST'))->toBeTrue()
        ->and(SettingsAccess::canAccessSection($editor, 'settings.hospital-info', 'GET'))->toBeFalse()
        ->and(SettingsAccess::canAccessSection($editor, 'settings.prescription-print', 'GET'))->toBeFalse();
});

it('handles a ModuleRegistry settings child that SettingsAccess does not name', function () {
    $slug = 'settings.hypothetical-share';
    $permission = 'access settings.hypothetical-share';

    $moduleRef = new ReflectionClass(\App\Models\ModuleRegistry::class);
    $moduleProp = $moduleRef->getProperty('modules');
    $moduleProp->setAccessible(true);
    $originalModules = $moduleProp->getValue();

    $permissionRef = new ReflectionClass(\App\Support\PermissionRegistry::class);
    $permissionProp = $permissionRef->getProperty('modules');
    $permissionProp->setAccessible(true);
    $originalPermissions = $permissionProp->getValue();

    try {
        $modules = $originalModules;
        $modules[$slug] = [
            'name' => 'Hypothetical Share',
            'group' => 'Admin',
            'parent' => 'settings',
            'entitlement' => 'plan',
            'routes' => ['settings.hypothetical-share'],
        ];
        $moduleProp->setValue(null, $modules);

        $permissions = $originalPermissions;
        $permissions[$slug] = [
            'label' => 'Hypothetical Share',
            'groups' => [
                'settings' => [$permission],
            ],
        ];
        $permissionProp->setValue(null, $permissions);

        $allowed = settingsUser([$permission]);
        $parentOnly = settingsUser(['access settings']);

        expect(SettingsAccess::canAccessSection($allowed, $slug, 'GET'))->toBeTrue()
            ->and(SettingsAccess::canAccessSection($allowed, $slug, 'POST'))->toBeTrue()
            ->and(SettingsAccess::canAccessAnySection($allowed))->toBeTrue()
            ->and(SettingsAccess::canAccessSection($parentOnly, $slug, 'GET'))->toBeFalse()
            ->and(file_get_contents((new ReflectionClass(SettingsAccess::class))->getFileName()))
            ->not->toContain($slug)
            ->not->toContain("'settings.hospital-info'")
            ->not->toContain("'settings.prescription-print'");
    } finally {
        $moduleProp->setValue(null, $originalModules);
        $permissionProp->setValue(null, $originalPermissions);
    }
});
