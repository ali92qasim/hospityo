<?php

use App\Http\Middleware\EnsureSettingsSection;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    Route::middleware(['web', 'auth', 'settings.section:settings.hospital-info'])
        ->get('/__test/settings-hospital', fn () => 'ok')
        ->name('test.settings.hospital');
});

function gateUser(array $permissions): User
{
    foreach ($permissions as $name) {
        Permission::findOrCreate($name, 'web');
    }
    $user = User::create([
        'name' => 'Gate',
        'email' => 'gate-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    if ($permissions !== []) {
        $user->givePermissionTo($permissions);
    }
    return $user;
}

it('allows parent access settings through settings.section middleware', function () {
    $this->actingAs(gateUser(['access settings']))
        ->get('/__test/settings-hospital')
        ->assertOk();
});

it('forbids users without settings access', function () {
    $this->actingAs(gateUser([]))
        ->get('/__test/settings-hospital')
        ->assertForbidden();
});
