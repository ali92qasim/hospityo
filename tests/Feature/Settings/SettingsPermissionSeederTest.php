<?php

use App\Models\Role;
use App\Support\PermissionRegistry;
use App\Support\SettingsAccess;
use App\Support\SettingsSectionRegistry;
use Database\Seeders\RolePermissionSeeder;
use App\Models\User;

it('seeds access settings permissions into the registry and receptionist parent grant', function () {
    $this->seed(RolePermissionSeeder::class);

    $names = PermissionRegistry::flat();
    expect($names)->toContain('access settings')
        ->and($names)->toContain('access settings.hospital-info')
        ->and($names)->toContain('access settings.prescription-print');

    $receptionist = Role::findByName('Receptionist', 'web');
    expect($receptionist->hasPermissionTo('access settings'))->toBeTrue();

    $hospitalAdmin = Role::findByName('Hospital Administrator', 'web');
    expect($hospitalAdmin->hasPermissionTo('access settings'))->toBeTrue();

    $superAdminRole = Role::findByName('Super Admin', 'web');
    expect($superAdminRole->hasPermissionTo('access settings'))->toBeTrue()
        ->and($superAdminRole->hasPermissionTo('access settings.hospital-info'))->toBeTrue();
});

it('gives a tenant Super Admin user helper access without a helper special case', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::create([
        'name' => 'Clinic Super Admin',
        'email' => 'clinic-sa-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $user->assignRole('Super Admin');

    expect($user->can('access settings'))->toBeTrue()
        ->and(SettingsAccess::canAccessSection($user, 'settings.hospital-info', 'GET'))->toBeTrue()
        ->and(SettingsAccess::canAccessSection($user, 'settings.prescription-print', 'POST'))->toBeTrue();
});
