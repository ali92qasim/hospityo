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

    Permission::findOrCreate('view prescriptions', 'web');
    Permission::findOrCreate('dispense pharmacy', 'web');

    $this->user = User::create([
        'name' => 'Rx Dispense User',
        'email' => 'rx-dispense-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo(['view prescriptions', 'dispense pharmacy']);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);
    $this->withoutVite();
    $this->actingAs($this->user);

    $tab = Unit::create([
        'name' => 'TAB/CAP',
        'abbreviation' => 'TAB',
        'conversion_factor' => 1,
        'type' => 'solid',
        'is_active' => true,
    ]);

    $category = MedicineCategory::create(['code' => 'TAB', 'name' => 'Tablets', 'is_active' => true]);

    $this->medicine = Medicine::create([
        'name' => 'Rx Dispense Med',
        'sku' => 'RX-DISP-'.uniqid(),
        'category_id' => $category->id,
        'base_unit_id' => $tab->id,
        'dispensing_unit_id' => $tab->id,
        'manage_stock' => true,
        'status' => 'active',
        'selling_price' => 50,
    ]);

    InventoryTransaction::create([
        'medicine_id' => $this->medicine->id,
        'type' => 'stock_in',
        'quantity' => 20,
        'remaining_quantity' => 20,
        'unit_cost' => 10,
        'total_cost' => 200,
        'batch_no' => 'RX-BATCH-1',
        'expiry_date' => now()->addYear(),
        'created_by' => $this->user->id,
    ]);

    $this->patient = Patient::create([
        'name' => 'Rx Patient',
        'gender' => 'female',
        'age' => 30,
        'phone' => '03001234567',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03001234568',
        'emergency_relation' => 'Spouse',
    ]);

    $department = Department::create(['name' => 'General', 'code' => 'GEN', 'status' => 'active']);

    $this->doctor = Doctor::create([
        'name' => 'Dr Rx Test',
        'doctor_no' => 'DOC-RX-001',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03001234569',
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
        'visit_datetime' => now(),
        'status' => 'with_doctor',
    ]);
});

function pendingPrescriptionForDispenseTest(object $context): Prescription
{
    $prescription = Prescription::create([
        'visit_id' => $context->visit->id,
        'patient_id' => $context->patient->id,
        'doctor_id' => $context->doctor->id,
        'status' => 'pending',
        'prescribed_date' => now(),
        'total_amount' => 100,
    ]);

    PrescriptionItem::create([
        'prescription_id' => $prescription->id,
        'medicine_id' => $context->medicine->id,
        'quantity' => 2,
        'unit_price' => 50,
        'total_price' => 100,
    ]);

    return $prescription;
}

it('registers prescriptions.dispense pointing at PrescriptionController dispense', function () {
    $route = app('router')->getRoutes()->getByName('prescriptions.dispense');

    expect($route)->not->toBeNull()
        ->and($route->methods())->toContain('POST')
        ->and($route->getAction('controller'))->toBe(\App\Http\Controllers\PrescriptionController::class.'@dispense');
});

it('renders prescriptions index when pending rows include dispense action', function () {
    pendingPrescriptionForDispenseTest($this);

    $this->get(route('prescriptions.index'))->assertOk();
});

it('renders prescriptions show for a pending prescription', function () {
    $prescription = pendingPrescriptionForDispenseTest($this);

    $this->get(route('prescriptions.show', $prescription))->assertOk();
});

it('dispenses a pending prescription via prescriptions.dispense', function () {
    $prescription = pendingPrescriptionForDispenseTest($this);

    $this->post(route('prescriptions.dispense', $prescription))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($prescription->fresh()->status)->toBe('dispensed')
        ->and($prescription->fresh()->dispensed_date)->not->toBeNull()
        ->and(InventoryTransaction::where('type', 'stock_out')->count())->toBe(1);
});
