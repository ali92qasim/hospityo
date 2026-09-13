<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach ([
        'view investigation orders',
        'view investigations',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::create([
        'name' => 'Flash Once User',
        'email' => 'flash-once-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo([
        'view investigation orders',
        'view investigations',
    ]);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user);
});

it('shows a lab order success flash only once on the orders index', function () {
    $message = 'Lab order created successfully.';

    $html = $this->withSession(['success' => $message])
        ->get(route('lab.orders.index'))
        ->assertOk()
        ->getContent();

    expect(substr_count($html, $message))->toBe(1)
        ->and($html)->toContain('window.Toast?.success')
        ->and($html)->not->toContain('bg-green-50 border border-green-200');
});

it('shows an imaging study delete flash only once on the studies index', function () {
    $message = 'Imaging study deleted successfully.';

    $html = $this->withSession(['success' => $message])
        ->get(route('imaging.studies.index'))
        ->assertOk()
        ->getContent();

    expect(substr_count($html, $message))->toBe(1)
        ->and($html)->toContain('window.Toast?.success')
        ->and($html)->not->toContain('bg-green-50 border border-green-200');
});
