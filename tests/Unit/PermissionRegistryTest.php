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
