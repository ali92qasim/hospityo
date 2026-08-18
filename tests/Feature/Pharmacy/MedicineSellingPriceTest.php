<?php

use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Models\Permission;
use App\Models\Unit;
use App\Models\User;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Pharmacy Manager',
        'email' => 'medicine-selling-price@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('manage pharmacy', 'web');
    $this->user->givePermissionTo('manage pharmacy');
    $this->actingAs($this->user);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->category = MedicineCategory::create([
        'code' => 'TAB',
        'name' => 'Tablets',
        'is_active' => true,
    ]);

    $this->unit = Unit::create([
        'name' => 'TAB/CAP',
        'abbreviation' => 'TAB',
        'conversion_factor' => 1,
        'type' => 'solid',
        'is_active' => true,
    ]);
});

it('requires selling_price when creating a medicine', function () {
    $response = $this->post(route('medicines.store'), [
        'name' => 'Unpriced Medicine',
        'status' => 'active',
        'manage_stock' => '1',
    ]);

    $response->assertSessionHasErrors('selling_price');
    expect(Medicine::where('name', 'Unpriced Medicine')->exists())->toBeFalse();
});

it('persists selling_price when creating a medicine', function () {
    $response = $this->post(route('medicines.store'), [
        'name' => 'Priced Medicine',
        'selling_price' => '25.50',
        'status' => 'active',
        'manage_stock' => '1',
    ]);

    $response->assertRedirect(route('medicines.index'));

    $medicine = Medicine::where('name', 'Priced Medicine')->first();

    expect($medicine)->not->toBeNull()
        ->and((float) $medicine->selling_price)->toBe(25.5);
});

it('requires selling_price when updating a medicine', function () {
    $medicine = Medicine::create([
        'name' => 'Existing Medicine',
        'sku' => 'EXISTING-MED-001',
        'category_id' => $this->category->id,
        'base_unit_id' => $this->unit->id,
        'purchase_unit_id' => $this->unit->id,
        'dispensing_unit_id' => $this->unit->id,
        'selling_price' => 10.0,
        'manage_stock' => true,
        'status' => 'active',
    ]);

    $response = $this->put(route('medicines.update', $medicine), [
        'name' => $medicine->name,
        'status' => 'active',
        'manage_stock' => '1',
    ]);

    $response->assertSessionHasErrors('selling_price');
});
