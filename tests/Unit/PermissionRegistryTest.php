<?php

use App\Support\PermissionRegistry;
use Database\Seeders\RolePermissionSeeder;

function rolePermissionSeederPermissions(): array
{
    return (new ReflectionClass(RolePermissionSeeder::class))->getConstant('PERMISSIONS');
}

it('lists hr permissions under hr group', function () {
    $groups = PermissionRegistry::grouped();

    expect($groups)->toHaveKey('hr');
});

it('flat list has no duplicate permission strings', function () {
    $flat = PermissionRegistry::flat();

    expect($flat)->toBe(array_values(array_unique($flat)))
        ->and(count($flat))->toBe(count(array_unique($flat)));
});

it('flat returns the same list as all', function () {
    expect(PermissionRegistry::flat())->toBe(PermissionRegistry::all());
});

it('flat list includes every permission from RolePermissionSeeder', function () {
    $seederPermissions = rolePermissionSeederPermissions();
    $registryPermissions = PermissionRegistry::flat();

    foreach ($seederPermissions as $permission) {
        expect($registryPermissions)->toContain($permission);
    }

    expect(count($seederPermissions))->toBeLessThanOrEqual(count($registryPermissions));
});

it('registers unique permission lists for accounting and pharmacy catalog children', function () {
    expect(PermissionRegistry::forModule('accounting.profit-loss'))->toBe(['view profit and loss'])
        ->and(PermissionRegistry::forModule('pharmacy.pos'))->toEqualCanonicalizing(['view pos', 'dispense pharmacy'])
        ->and(PermissionRegistry::forModule('accounting'))->toContain('view chart of accounts')
        ->and(PermissionRegistry::forModule('accounting'))->not->toContain('view profit and loss')
        ->and(PermissionRegistry::forModule('pharmacy'))->toContain('view prescriptions')
        ->and(PermissionRegistry::forModule('pharmacy'))->not->toContain('view pos');

    $flat = PermissionRegistry::flat();
    expect($flat)->toBe(array_values(array_unique($flat)));
});

it('splits settings permission registry so parent forModule excludes child names', function () {
    expect(PermissionRegistry::forModule('settings'))->not->toContain('access settings.prescription-print')
        ->and(PermissionRegistry::forModule('settings'))->toEqualCanonicalizing([
            'access settings',
            'manage settings',
            'view settings',
            'edit settings',
        ])
        ->and(PermissionRegistry::forModule('settings.prescription-print'))->toBe(['access settings.prescription-print'])
        ->and(PermissionRegistry::forModule('settings.hospital-info'))->toBe(['access settings.hospital-info']);

    $flat = PermissionRegistry::flat();
    expect($flat)->toBe(array_values(array_unique($flat)));
});
