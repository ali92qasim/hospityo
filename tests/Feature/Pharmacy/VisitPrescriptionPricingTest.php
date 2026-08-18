<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\InventoryTransaction;
use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Models\Patient;
use App\Models\PrescriptionItem;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\Visit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $tenant = new Tenant;
    $tenant->id = 1;
    $tenant->status = 'active';
    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    $this->user = User::create([
        'name' => 'Prescription Pricing User',
        'email' => 'visit-rx-pricing@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('edit visits', 'web');
    $this->user->givePermissionTo('edit visits');

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user);

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

    $this->patient = Patient::create([
        'name' => 'Rx Pricing Patient',
        'gender' => 'male',
        'age' => 35,
        'phone' => '03001112233',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03004445566',
        'emergency_relation' => 'Spouse',
    ]);

    $department = Department::create(['name' => 'OPD', 'code' => 'OPD', 'status' => 'active']);

    $this->doctor = Doctor::create([
        'name' => 'Dr. Rx',
        'doctor_no' => 'DOC-RX-001',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03006667788',
        'email' => 'dr-rx@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $this->visit = Visit::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now(),
    ]);
});

it('snapshots catalog selling price on visit prescription lines', function () {
    $medicine = Medicine::create([
        'name' => 'Priced Med',
        'sku' => 'PRICED-MED-001',
        'category_id' => $this->category->id,
        'base_unit_id' => $this->tab->id,
        'purchase_unit_id' => $this->tab->id,
        'dispensing_unit_id' => $this->tab->id,
        'selling_price' => 20.0,
        'manage_stock' => true,
        'status' => 'active',
    ]);

    InventoryTransaction::create([
        'medicine_id' => $medicine->id,
        'type' => 'stock_in',
        'quantity' => 100,
        'remaining_quantity' => 100,
        'unit_cost' => 8.0,
        'total_cost' => 800.0,
        'batch_no' => 'BATCH-RX-001',
        'created_by' => $this->user->id,
    ]);

    $this->post(route('visits.prescription', $this->visit), [
        'medicines' => [
            ['medicine_id' => $medicine->id, 'quantity' => 2],
        ],
    ])->assertRedirect()->assertSessionHas('success');

    $item = PrescriptionItem::first();

    expect($item)->not->toBeNull()
        ->and((float) $item->unit_price)->toBe(20.0)
        ->and((float) $item->total_price)->toBe(40.0);
});
