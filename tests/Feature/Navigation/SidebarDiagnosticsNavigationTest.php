<?php

use App\Models\Tenant;
use App\Models\User;
use App\Services\SidebarService;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->service = new SidebarService;

    foreach ([
        'view investigations',
        'view investigation orders',
        'view lab orders',
        'view lab results',
        'view radiology results',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
});

function diagnosticsNavUser(): User
{
    $user = User::create([
        'name' => 'Diagnostics Nav User',
        'email' => 'diag-nav-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo([
        'view investigations',
        'view investigation orders',
        'view lab results',
        'view radiology results',
    ]);

    return $user;
}

function laboratoryTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class);
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => $module === 'laboratory');

    return $tenant;
}

it('shows laboratory and imaging groups instead of diagnostics', function () {
    $menu = $this->service->build(diagnosticsNavUser(), laboratoryTenant());
    $labels = collect($menu)->pluck('label');

    expect($labels)->toContain('Laboratory', 'Imaging')
        ->not->toContain('Diagnostics');
});

it('lists kind-specific child links in each diagnostics group', function () {
    $menu = $this->service->build(diagnosticsNavUser(), laboratoryTenant());

    $lab = collect($menu)->firstWhere('label', 'Laboratory');
    $imaging = collect($menu)->firstWhere('label', 'Imaging');

    expect(collect($lab['items'])->pluck('label')->all())->toBe([
        'Lab Tests',
        'Lab Orders',
        'Lab Results',
    ])->and(collect($imaging['items'])->pluck('label')->all())->toBe([
        'Imaging Studies',
        'Imaging Orders',
        'Imaging Reports',
    ]);
});

it('hides laboratory group when user lacks laboratory permissions', function () {
    $user = User::create([
        'name' => 'No Lab User',
        'email' => 'no-lab-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $menu = $this->service->build($user, laboratoryTenant());
    $labels = collect($menu)->pluck('label');

    expect($labels)->not->toContain('Laboratory', 'Imaging', 'Diagnostics');
});

it('shows only lab results when user lacks radiology permission', function () {
    $user = User::create([
        'name' => 'Lab Only User',
        'email' => 'lab-only-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo(['view investigations', 'view investigation orders', 'view lab results']);

    $menu = $this->service->build($user, laboratoryTenant());

    $lab = collect($menu)->firstWhere('label', 'Laboratory');
    $imaging = collect($menu)->firstWhere('label', 'Imaging');

    expect($lab)->not->toBeNull()
        ->and(collect($lab['items'])->pluck('label'))->toContain('Lab Results')
        ->and($imaging)->toBeNull();
});
