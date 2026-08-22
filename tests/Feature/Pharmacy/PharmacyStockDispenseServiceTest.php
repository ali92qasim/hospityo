<?php

use App\Exceptions\InsufficientPharmacyStockException;
use App\Models\InventoryTransaction;
use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\PharmacyStockDispenseService;

beforeEach(function () {
    $tenant = new Tenant;
    $tenant->id = 1;
    $tenant->status = 'active';
    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    $this->user = User::create([
        'name' => 'Pharmacist',
        'email' => 'pharmacist-dispense@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $tab = Unit::create([
        'name' => 'TAB/CAP',
        'abbreviation' => 'TAB',
        'conversion_factor' => 1,
        'type' => 'solid',
        'is_active' => true,
    ]);

    $category = MedicineCategory::create(['code' => 'TAB', 'name' => 'Tablets', 'is_active' => true]);

    $this->medicine = Medicine::create([
        'name' => 'Dispense Test Med',
        'sku' => 'DISP-001',
        'category_id' => $category->id,
        'base_unit_id' => $tab->id,
        'dispensing_unit_id' => $tab->id,
        'manage_stock' => true,
        'status' => 'active',
        'selling_price' => 25,
    ]);

    $this->batch = InventoryTransaction::create([
        'medicine_id' => $this->medicine->id,
        'type' => 'stock_in',
        'quantity' => 10,
        'remaining_quantity' => 10,
        'unit_cost' => 15,
        'total_cost' => 150,
        'batch_no' => 'BATCH-001',
        'expiry_date' => now()->addYear(),
        'created_by' => $this->user->id,
    ]);
});

it('dispense lines creates stock out and decrements batch', function () {
    $service = app(PharmacyStockDispenseService::class);

    $service->dispenseLines([
        ['medicine' => $this->medicine, 'quantity' => 3, 'reference' => 'BILL-001'],
    ], $this->user->id);

    expect(InventoryTransaction::where('type', 'stock_out')->count())->toBe(1)
        ->and(InventoryTransaction::where('type', 'stock_out')->first()->reference_no)->toBe('BILL-001')
        ->and($this->batch->fresh()->remaining_quantity)->toBe(7);
});

it('throws when stock is insufficient', function () {
    $service = app(PharmacyStockDispenseService::class);

    expect(fn () => $service->dispenseLines([
        ['medicine' => $this->medicine, 'quantity' => 20, 'reference' => 'BILL-002'],
    ], $this->user->id))->toThrow(InsufficientPharmacyStockException::class);
});

it('skips stock deduction for medicines that do not manage stock', function () {
    $this->medicine->update(['manage_stock' => false]);

    $service = app(PharmacyStockDispenseService::class);
    $service->dispenseLines([
        ['medicine' => $this->medicine->fresh(), 'quantity' => 5, 'reference' => 'BILL-003'],
    ], $this->user->id);

    expect(InventoryTransaction::where('type', 'stock_out')->count())->toBe(0)
        ->and($this->batch->fresh()->remaining_quantity)->toBe(10);
});
