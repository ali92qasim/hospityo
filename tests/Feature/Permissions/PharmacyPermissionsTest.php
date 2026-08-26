<?php

use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);
});

function bindPharmacyTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => $module === 'pharmacy');

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function pharmacyPermissionUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Pharmacy Permission User',
        'email' => 'pharmacy-perm-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

it('allows POS with dispense pharmacy only', function () {
    bindPharmacyTenant();
    $this->actingAs(pharmacyPermissionUser(['dispense pharmacy']));

    $this->get(route('pharmacy.pos.index'))
        ->assertOk();
});

it('blocks medicine edit without edit medicines permission', function () {
    bindPharmacyTenant();

    $tab = Unit::create([
        'name' => 'TAB/CAP',
        'abbreviation' => 'TAB',
        'conversion_factor' => 1,
        'type' => 'solid',
        'is_active' => true,
    ]);

    $category = MedicineCategory::create(['code' => 'TAB', 'name' => 'Tablets', 'is_active' => true]);

    $medicine = Medicine::create([
        'name' => 'Permission Test Medicine',
        'sku' => 'PERM-MED-001',
        'category_id' => $category->id,
        'base_unit_id' => $tab->id,
        'dispensing_unit_id' => $tab->id,
        'manage_stock' => false,
        'status' => 'active',
        'selling_price' => 50,
    ]);

    $this->actingAs(pharmacyPermissionUser(['view medicines']));

    $this->get(route('medicines.edit', $medicine))
        ->assertForbidden();
});

it('allows medicine index with view medicines', function () {
    bindPharmacyTenant();
    $this->actingAs(pharmacyPermissionUser(['view medicines']));

    $this->get(route('medicines.index'))
        ->assertOk();
});

it('does not crash when opening a medicine show url', function () {
    bindPharmacyTenant();

    $tab = Unit::create([
        'name' => 'TAB/CAP',
        'abbreviation' => 'TAB',
        'conversion_factor' => 1,
        'type' => 'solid',
        'is_active' => true,
    ]);

    $category = MedicineCategory::create(['code' => 'TAB', 'name' => 'Tablets', 'is_active' => true]);

    $medicine = Medicine::create([
        'name' => 'Show Url Medicine',
        'sku' => 'SHOW-MED-001',
        'category_id' => $category->id,
        'base_unit_id' => $tab->id,
        'dispensing_unit_id' => $tab->id,
        'manage_stock' => false,
        'status' => 'active',
        'selling_price' => 50,
    ]);

    $this->actingAs(pharmacyPermissionUser(['view medicines']));

    $this->get('/medicines/'.$medicine->id)
        ->assertMethodNotAllowed();

    expect(\Illuminate\Support\Facades\Route::has('medicines.show'))->toBeFalse();
});
