<?php

use App\Support\PermissionRegistry;

it('lists hr permissions under hr group', function () {
    $groups = PermissionRegistry::grouped();

    expect($groups)->toHaveKey('hr');
});

it('all flat permissions are unique', function () {
    $all = PermissionRegistry::all();

    expect($all)->toBe(array_unique($all));
});

it('flat returns the same list as all', function () {
    expect(PermissionRegistry::flat())->toBe(PermissionRegistry::all());
});

it('includes every permission from RolePermissionSeeder', function () {
    $seederPermissions = (new ReflectionClass(Database\Seeders\RolePermissionSeeder::class))
        ->getConstant('PERMISSIONS');

    $registryPermissions = PermissionRegistry::all();

    foreach ($seederPermissions as $permission) {
        expect($registryPermissions)->toContain($permission);
    }
});
