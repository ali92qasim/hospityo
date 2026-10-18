<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\Bed;
use App\Models\Department;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Ward;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| Wards / Beds UI gating (RBAC wave 2, Task 13)
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
    $this->withoutVite();

    $this->artisan('migrate', [
        '--path' => 'database/migrations/tenant',
        '--database' => 'tenant',
    ]);

    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => $module === 'ipd');
    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    $this->department = Department::create([
        'name' => 'WB Ui Med',
        'code' => 'WB-UI-'.uniqid(),
        'status' => 'active',
    ]);
    $this->ward = Ward::create([
        'name' => 'WB Ui Ward',
        'department_id' => $this->department->id,
        'capacity' => 6,
        'ward_type' => 'general',
        'status' => 'active',
    ]);
    $this->bed = Bed::create([
        'bed_number' => 'WB-UI-01',
        'ward_id' => $this->ward->id,
        'bed_type' => 'general',
        'daily_rate' => 400,
        'status' => 'available',
    ]);
});

function wbUiUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'WB Ui User',
        'email' => 'wb-ui-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo($permissions);

    return $user;
}

function wbUiHref(string $route, array $params = []): string
{
    return 'href="'.e(route($route, $params)).'"';
}

function wbUiDestroyAction(string $route, int $id): string
{
    return 'action="'.e(route($route, $id)).'"';
}

function wbUiStatusFormAction(Bed $bed): string
{
    return 'action="'.e(route('beds.update-status', $bed)).'"';
}

it('hides ward Add Edit Delete from a view-only user', function () {
    $this->actingAs(wbUiUser(['view wards']));

    $this->get(route('wards.index'))
        ->assertOk()
        ->assertSee('WB Ui Ward')
        ->assertDontSee(wbUiHref('wards.create'), false)
        ->assertDontSee(wbUiHref('wards.edit', ['ward' => $this->ward->id]), false)
        ->assertDontSee(wbUiDestroyAction('wards.destroy', $this->ward->id), false);
});

it('hides bed Add Edit Delete and status control from a view-only user', function () {
    $this->actingAs(wbUiUser(['view beds']));

    $this->get(route('beds.index'))
        ->assertOk()
        ->assertSee('WB-UI-01')
        ->assertDontSee(wbUiHref('beds.create'), false)
        ->assertDontSee(wbUiHref('beds.edit', ['bed' => $this->bed->id]), false)
        ->assertDontSee(wbUiDestroyAction('beds.destroy', $this->bed->id), false)
        ->assertDontSee(wbUiStatusFormAction($this->bed), false)
        ->assertDontSee('data-bed-status-form', false);
});

it('shows the bed status control to a change-bed-status Nurse without Edit Delete or Add', function () {
    $this->seed(RolePermissionSeeder::class);

    $nurse = User::create([
        'name' => 'WB Ui Nurse',
        'email' => 'wb-ui-nurse-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $nurse->assignRole('Nurse');
    $this->actingAs($nurse);

    $this->get(route('beds.index'))
        ->assertOk()
        ->assertSee(wbUiStatusFormAction($this->bed), false)
        ->assertSee('data-bed-status-form', false)
        ->assertDontSee(wbUiHref('beds.create'), false)
        ->assertDontSee(wbUiHref('beds.edit', ['bed' => $this->bed->id]), false)
        ->assertDontSee(wbUiDestroyAction('beds.destroy', $this->bed->id), false);

    $this->get(route('wards.index'))
        ->assertOk()
        ->assertDontSee(wbUiHref('wards.create'), false)
        ->assertDontSee(wbUiHref('wards.edit', ['ward' => $this->ward->id]), false)
        ->assertDontSee(wbUiDestroyAction('wards.destroy', $this->ward->id), false);
});

it('shows status control and Edit for edit beds but Delete only with delete beds', function () {
    $this->actingAs(wbUiUser(['view beds', 'edit beds']));

    $this->get(route('beds.index'))
        ->assertOk()
        ->assertSee(wbUiStatusFormAction($this->bed), false)
        ->assertSee(wbUiHref('beds.edit', ['bed' => $this->bed->id]), false)
        ->assertDontSee(wbUiHref('beds.create'), false)
        ->assertDontSee(wbUiDestroyAction('beds.destroy', $this->bed->id), false);
});

it('shows create and edit for seeded HA but hides destroy on wards and beds', function () {
    $this->seed(RolePermissionSeeder::class);

    $ha = User::create([
        'name' => 'WB Ui HA',
        'email' => 'wb-ui-ha-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $ha->assignRole('Hospital Administrator');
    $this->actingAs($ha);

    $this->get(route('wards.index'))
        ->assertOk()
        ->assertSee(wbUiHref('wards.create'), false)
        ->assertSee(wbUiHref('wards.edit', ['ward' => $this->ward->id]), false)
        ->assertDontSee(wbUiDestroyAction('wards.destroy', $this->ward->id), false);

    $this->get(route('beds.index'))
        ->assertOk()
        ->assertSee(wbUiHref('beds.create'), false)
        ->assertSee(wbUiHref('beds.edit', ['bed' => $this->bed->id]), false)
        ->assertSee(wbUiStatusFormAction($this->bed), false)
        ->assertDontSee(wbUiDestroyAction('beds.destroy', $this->bed->id), false);
});
