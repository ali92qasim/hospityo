<?php

use App\Models\InventoryTransaction;
use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;

beforeEach(function () {
    $tenant = new Tenant;
    $tenant->id = 1;
    $tenant->status = 'active';
    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    $this->user = User::create([
        'name' => 'Pharmacy Manager',
        'email' => 'stock-in-batch@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    Permission::findOrCreate('manage pharmacy', 'web');
    $this->user->givePermissionTo('manage pharmacy');
    $this->actingAs($this->user);

    $this->tab = Unit::create([
        'name' => 'TAB/CAP', 'abbreviation' => 'TAB', 'conversion_factor' => 1,
        'type' => 'solid', 'is_active' => true,
    ]);
    $this->p10 = Unit::create([
        'name' => 'PACKING 10', 'abbreviation' => 'P10', 'base_unit_id' => $this->tab->id,
        'conversion_factor' => 10, 'type' => 'packaging', 'is_active' => true,
    ]);
    $this->category = MedicineCategory::create(['code' => 'TAB', 'name' => 'Tablets', 'is_active' => true]);
    $this->medicine = Medicine::create([
        'name' => 'Test Med', 'sku' => 'TEST-MED-001', 'category_id' => $this->category->id,
        'base_unit_id' => $this->tab->id, 'purchase_unit_id' => $this->p10->id,
        'dispensing_unit_id' => $this->tab->id, 'manage_stock' => true, 'status' => 'active',
    ]);
});

it('adds stock via existing batch mode using locked batch cost', function () {
    $existingBatch = InventoryTransaction::create([
        'medicine_id' => $this->medicine->id,
        'type' => 'stock_in',
        'quantity' => 50,
        'remaining_quantity' => 50,
        'unit_cost' => 50.0,
        'total_cost' => 2500.0,
        'supplier' => 'Original Supplier',
        'batch_no' => 'BATCH-001',
        'expiry_date' => now()->addYear(),
        'created_by' => $this->user->id,
    ]);

    $this->post(route('inventory.process-stock-in'), [
        'stock_in_mode' => 'existing',
        'existing_batch_id' => $existingBatch->id,
        'medicine_id' => $this->medicine->id,
        'quantity' => 3,
        'unit_id' => $this->p10->id,
        'supplier' => 'Restock Supplier',
    ])->assertRedirect(route('inventory.index'));

    $newLine = InventoryTransaction::where('medicine_id', $this->medicine->id)
        ->where('id', '!=', $existingBatch->id)
        ->first();

    expect($newLine)->not->toBeNull()
        ->and($newLine->type)->toBe('stock_in')
        ->and($newLine->quantity)->toBe(30)
        ->and($newLine->remaining_quantity)->toBe(30)
        ->and((float) $newLine->unit_cost)->toBe(50.0)
        ->and((float) $newLine->total_cost)->toBe(1500.0)
        ->and($newLine->batch_no)->toBe('BATCH-001')
        ->and($newLine->expiry_date->toDateString())->toBe($existingBatch->expiry_date->toDateString());
});

it('rejects new mode when batch number exists with a different cost', function () {
    InventoryTransaction::create([
        'medicine_id' => $this->medicine->id,
        'type' => 'stock_in',
        'quantity' => 50,
        'remaining_quantity' => 50,
        'unit_cost' => 50.0,
        'total_cost' => 2500.0,
        'supplier' => 'Original Supplier',
        'batch_no' => 'BATCH-001',
        'expiry_date' => now()->addYear(),
        'created_by' => $this->user->id,
    ]);

    $this->post(route('inventory.process-stock-in'), [
        'stock_in_mode' => 'new',
        'medicine_id' => $this->medicine->id,
        'quantity' => 1,
        'unit_id' => $this->p10->id,
        'unit_cost' => 600,
        'supplier' => 'Another Supplier',
        'batch_no' => 'BATCH-001',
        'expiry_date' => now()->addMonths(6)->format('Y-m-d'),
    ])->assertSessionHasErrors('batch_no');

    expect(InventoryTransaction::count())->toBe(1);
});
