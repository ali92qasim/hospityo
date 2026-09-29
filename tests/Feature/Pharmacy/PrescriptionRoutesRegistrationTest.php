<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);
    $this->withoutVite();
});

function prescriptionsUser(array $permissions = [], ?string $roleName = null): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Rx User',
        'email' => 'rx-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    if ($permissions !== []) {
        $user->givePermissionTo($permissions);
    }

    if ($roleName) {
        $role = Role::findOrCreate($roleName, 'web');
        if ($permissions !== []) {
            $role->givePermissionTo($permissions);
        }
        $user->assignRole($role);
    }

    return $user;
}

it('registers PrescriptionController on the prescriptions resource routes', function () {
    $route = app('router')->getRoutes()->getByName('prescriptions.index');

    expect($route)->not->toBeNull();

    $action = $route->getAction('controller');

    expect($action)->toBe(\App\Http\Controllers\PrescriptionController::class.'@index');
});

it('can list routes including prescriptions without a BindingResolutionException', function () {
    $process = new Process([
        PHP_BINARY,
        'artisan',
        'route:list',
        '--name=prescriptions',
        '--json',
    ], base_path());
    $process->setTimeout(60);
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($process->getErrorOutput().$process->getOutput())
        ->not->toContain('PrescriptionController does not exist')
        ->and($process->getErrorOutput().$process->getOutput())
        ->not->toContain('Target class [PrescriptionController]');
});

it('loads prescriptions index for a user with edit visits', function () {
    $this->actingAs(prescriptionsUser(['edit visits']));

    $this->get(route('prescriptions.index'))->assertOk();
});

it('loads prescriptions create for a user with edit visits', function () {
    $this->actingAs(prescriptionsUser(['edit visits']));

    $this->get(route('prescriptions.create'))->assertOk();
});

it('forbids prescriptions index for a receptionist without edit visits', function () {
    $this->actingAs(prescriptionsUser([], 'Receptionist'));

    $this->get(route('prescriptions.index'))->assertForbidden();
});
