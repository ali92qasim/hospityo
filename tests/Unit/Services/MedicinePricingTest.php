<?php

use App\Models\InventoryTransaction;
use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\MedicinePricing;

beforeEach(function () {
    $tenant = new Tenant;
    $tenant->id = 1;
    $tenant->status = 'active';
    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    $this->user = User::create([
        'name' => 'Pharmacy Staff',
        'email' => 'pricing-test@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->tab = Unit::create([
        'name' => 'TAB/CAP',
        'abbreviation' => 'TAB',
        'conversion_factor' => 1,
        'type' => 'solid',
        'is_active' => true,
    ]);

    $this->category = MedicineCategory::create([
        'code' => 'TAB',
        'name' => 'Tablets',
        'is_active' => true,
    ]);
});

it('returns catalog selling price when set', function () {
    $medicine = Medicine::create([
        'name' => 'Paracetamol',
        'sku' => 'PARA-500-TAB',
        'category_id' => $this->category->id,
        'base_unit_id' => $this->tab->id,
        'purchase_unit_id' => $this->tab->id,
        'dispensing_unit_id' => $this->tab->id,
        'selling_price' => 12.50,
        'manage_stock' => true,
        'status' => 'active',
    ]);

    expect(MedicinePricing::hasSellingPrice($medicine))->toBeTrue()
        ->and(MedicinePricing::sellingPricePerBaseUnit($medicine))->toBe(12.5)
        ->and(MedicinePricing::snapshotLinePrice($medicine))->toBe(12.5);
});

it('does NOT fall back to batch cost when selling_price is null', function () {
    $medicine = Medicine::create([
        'name' => 'No Price Med',
        'sku' => 'NO-PRICE-001',
        'category_id' => $this->category->id,
        'base_unit_id' => $this->tab->id,
        'purchase_unit_id' => $this->tab->id,
        'dispensing_unit_id' => $this->tab->id,
        'selling_price' => null,
        'manage_stock' => true,
        'status' => 'active',
    ]);

    InventoryTransaction::create([
        'medicine_id' => $medicine->id,
        'type' => 'stock_in',
        'quantity' => 100,
        'remaining_quantity' => 100,
        'unit_cost' => 99.99,
        'total_cost' => 9999.0,
        'batch_no' => 'BATCH-A',
        'created_by' => $this->user->id,
    ]);

    expect(MedicinePricing::hasSellingPrice($medicine))->toBeFalse()
        ->and(MedicinePricing::sellingPricePerBaseUnit($medicine))->toBe(0.0)
        ->and(MedicinePricing::snapshotLinePrice($medicine))->toBe(0.0);
});

it('delegates getSellingPrice on model without cost fallback', function () {
    $medicine = Medicine::create([
        'name' => 'Catalog Only',
        'sku' => 'CAT-ONLY-001',
        'category_id' => $this->category->id,
        'base_unit_id' => $this->tab->id,
        'purchase_unit_id' => $this->tab->id,
        'dispensing_unit_id' => $this->tab->id,
        'selling_price' => null,
        'manage_stock' => true,
        'status' => 'active',
    ]);

    InventoryTransaction::create([
        'medicine_id' => $medicine->id,
        'type' => 'stock_in',
        'quantity' => 50,
        'remaining_quantity' => 50,
        'unit_cost' => 75.0,
        'total_cost' => 3750.0,
        'batch_no' => 'BATCH-B',
        'created_by' => $this->user->id,
    ]);

    expect($medicine->getSellingPrice())->toBe(0.0);

    $medicine->update(['selling_price' => 20.0]);

    expect($medicine->fresh()->getSellingPrice())->toBe(20.0);
});
