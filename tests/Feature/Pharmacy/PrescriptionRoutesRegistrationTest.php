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

it('loads prescriptions index for a user with view prescriptions', function () {
    $this->actingAs(prescriptionsUser(['view prescriptions']));

    $this->get(route('prescriptions.index'))->assertOk();
});

it('loads prescriptions create for a user with create prescriptions', function () {
    $this->actingAs(prescriptionsUser(['create prescriptions']));

    $this->get(route('prescriptions.create'))->assertOk();
});

it('forbids prescriptions index for a receptionist without edit visits', function () {
    $this->actingAs(prescriptionsUser([], 'Receptionist'));

    $this->get(route('prescriptions.index'))->assertForbidden();
});

it('registers no edit, update or destroy prescription routes', function () {
    foreach (['prescriptions.edit', 'prescriptions.update', 'prescriptions.destroy'] as $name) {
        expect(app('router')->getRoutes()->getByName($name))->toBeNull("{$name} must not be registered");
    }
});

it('gates the remaining prescription routes on pharmacy permissions', function () {
    $expected = [
        'prescriptions.index' => 'permission:view prescriptions|manage pharmacy',
        'prescriptions.show' => 'permission:view prescriptions|manage pharmacy',
        'prescriptions.create' => 'permission:create prescriptions',
        'prescriptions.store' => 'permission:create prescriptions',
        'prescriptions.dispense' => 'permission:dispense pharmacy|manage pharmacy',
        'visits.prescription' => 'permission:create prescriptions',
    ];

    foreach ($expected as $name => $middleware) {
        $route = app('router')->getRoutes()->getByName($name);

        expect($route)->not->toBeNull()
            ->and($route->gatherMiddleware())->toContain($middleware)
            ->and($route->gatherMiddleware())->not->toContain('permission:edit visits');
    }
});
