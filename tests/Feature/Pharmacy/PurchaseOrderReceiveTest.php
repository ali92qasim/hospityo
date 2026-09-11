<?php

use App\Models\InventoryTransaction;
use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Models\Permission;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
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
        'email' => 'po-receive@example.com',
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
    $this->supplier = Supplier::create([
        'name' => 'Test Supplier',
        'contact_person' => 'John Doe',
        'email' => 'supplier@example.com',
        'phone' => '03001234567',
        'address' => '123 Test St',
        'city' => 'Lahore',
        'country' => 'Pakistan',
        'status' => 'active',
    ]);
});

it('converts pack units to base units when receiving a purchase order', function () {
    $po = PurchaseOrder::create([
        'supplier_id' => $this->supplier->id,
        'order_date' => now(),
        'status' => 'approved',
        'created_by' => $this->user->id,
    ]);
    PurchaseOrderItem::create([
        'purchase_order_id' => $po->id,
        'medicine_id' => $this->medicine->id,
        'unit_id' => $this->p10->id,
        'quantity' => 5,
        'unit_price' => 500,
        'total_price' => 2500,
    ]);

    $this->post(route('purchases.receive', $po))->assertRedirect();

    $txn = InventoryTransaction::where('medicine_id', $this->medicine->id)->first();
    expect($txn->quantity)->toBe(50)
        ->and((float) $txn->unit_cost)->toBe(50.0)
        ->and((float) $txn->total_cost)->toBe(2500.0);
});

it('rejects receive when unit_id is missing on a line item', function () {
    $po = PurchaseOrder::create([
        'supplier_id' => $this->supplier->id,
        'order_date' => now(),
        'status' => 'approved',
        'created_by' => $this->user->id,
    ]);
    PurchaseOrderItem::create([
        'purchase_order_id' => $po->id,
        'medicine_id' => $this->medicine->id,
        'unit_id' => null,
        'quantity' => 5,
        'unit_price' => 500,
        'total_price' => 2500,
    ]);

    $this->post(route('purchases.receive', $po))
        ->assertRedirect()
        ->assertSessionHasErrors('error');

    expect(InventoryTransaction::count())->toBe(0);
});

it('renders the create purchase order page', function () {
    $this->withoutVite();
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->get(route('purchases.create'))
        ->assertOk()
        ->assertSee('Purchase Order Details', false)
        ->assertSee('window._purchaseMedicineUnits', false)
        ->assertSee('window._allUnits', false);
});
