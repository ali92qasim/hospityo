<?php

use App\Models\Permission;
use Database\Seeders\RolePermissionSeeder;

it('seeds granular hr permissions', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(Permission::where('name', 'view employees')->exists())->toBeTrue();
    expect(Permission::where('name', 'view payroll runs')->exists())->toBeTrue();
});
