<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\InventoryTransaction;
use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Models\Patient;
use App\Models\Prescription;
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
        'name' => 'Prescription Guard User',
        'email' => 'prescription-selling-price@example.com',
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
        'name' => 'Prescription Guard Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03001234567',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03007654321',
        'emergency_relation' => 'Spouse',
    ]);

    $department = Department::create(['name' => 'OPD', 'code' => 'OPD', 'status' => 'active']);

    $this->doctor = Doctor::create([
        'name' => 'Dr. Guard',
        'doctor_no' => 'DOC-GUARD-001',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03009876543',
        'email' => 'dr-guard@example.com',
        'gender' => 'male',
        'experience_years' => 8,
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

    $this->unpricedMedicine = Medicine::create([
        'name' => 'Unpriced Medicine',
        'sku' => 'UNPRICED-MED-001',
        'category_id' => $this->category->id,
        'base_unit_id' => $this->tab->id,
        'purchase_unit_id' => $this->tab->id,
        'dispensing_unit_id' => $this->tab->id,
        'selling_price' => null,
        'manage_stock' => true,
        'status' => 'active',
    ]);

    InventoryTransaction::create([
        'medicine_id' => $this->unpricedMedicine->id,
        'type' => 'stock_in',
        'quantity' => 50,
        'remaining_quantity' => 50,
        'unit_cost' => 5.0,
        'total_cost' => 250.0,
        'batch_no' => 'BATCH-UNPRICED-001',
        'created_by' => $this->user->id,
    ]);
});

it('rejects visit prescriptions for medicines without selling price', function () {
    $this->post(route('visits.prescription', $this->visit), [
        'medicines' => [
            ['medicine_id' => $this->unpricedMedicine->id, 'quantity' => 1],
        ],
    ])->assertSessionHasErrors('medicines.0.medicine_id');

    expect(PrescriptionItem::count())->toBe(0);
});

it('rejects standalone prescriptions for medicines without selling price', function () {
    $this->post(route('prescriptions.store'), [
        'visit_id' => $this->visit->id,
        'medicines' => [
            [
                'medicine_id' => $this->unpricedMedicine->id,
                'quantity' => 1,
                'dosage' => '1 tab',
                'frequency' => 'BD',
                'duration' => '5 days',
            ],
        ],
    ])->assertSessionHasErrors('medicines.0.medicine_id');

    expect(Prescription::count())->toBe(0);
});
