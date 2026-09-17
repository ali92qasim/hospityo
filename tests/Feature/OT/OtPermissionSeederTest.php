<?php

it('seeds manage pac on hospital administrator and not on doctor or nurse', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    expect(\Spatie\Permission\Models\Permission::where('name', 'manage pac')->exists())->toBeTrue()
        ->and(\App\Models\Role::findByName('Hospital Administrator', 'web')->hasPermissionTo('manage pac'))->toBeTrue()
        ->and(\App\Models\Role::findByName('Doctor', 'web')->hasPermissionTo('manage pac'))->toBeFalse()
        ->and(\App\Models\Role::findByName('Nurse', 'web')->hasPermissionTo('manage surgical checklists'))->toBeTrue()
        ->and(\App\Models\Role::findByName('Nurse', 'web')->hasPermissionTo('manage pac'))->toBeFalse();
});
