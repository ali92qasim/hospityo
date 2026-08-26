<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\InventoryTransaction;
use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Models\Patient;
use App\Models\Prescription;
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
        'name' => 'Prescription Doctor',
        'email' => 'rx-doctor@example.com',
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

    $this->patient = Patient::create([
        'name' => 'Rx Patient',
        'gender' => 'male',
        'age' => 30,
        'phone' => '03001234001',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03001234002',
        'emergency_relation' => 'Spouse',
    ]);

    $department = Department::create(['name' => 'Medicine', 'code' => 'MED', 'status' => 'active']);

    $this->doctor = Doctor::create([
        'name' => 'Dr. Rx',
        'doctor_no' => 'DOC-RX-001',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03001234003',
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

    $tab = Unit::create([
        'name' => 'TAB/CAP',
        'abbreviation' => 'TAB',
        'conversion_factor' => 1,
        'type' => 'solid',
        'is_active' => true,
    ]);

    $category = MedicineCategory::create(['code' => 'TAB', 'name' => 'Tablets', 'is_active' => true]);

    $this->medicine = Medicine::create([
        'name' => 'Rx Medicine',
        'sku' => 'RX-MED-001',
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
        'quantity' => 100,
        'remaining_quantity' => 100,
        'unit_cost' => 30,
        'total_cost' => 3000,
        'batch_no' => 'BATCH-RX-1',
        'expiry_date' => now()->addYear(),
        'created_by' => $this->user->id,
    ]);
});

it('workflow prescription form does not ask where the patient will get medicines', function () {
    $panel = file_get_contents(resource_path('views/admin/visits/workflow/_shared/_prescription-panel.blade.php'));

    expect($panel)->not->toContain('Where will the patient get these medicines?')
        ->and($panel)->not->toContain('name="fulfillment_type"')
        ->and($panel)->toContain('Create Prescription');

    expect(file_get_contents(resource_path('views/admin/visits/workflow/opd/_layout.blade.php')))
        ->toContain('_shared._prescription-panel');
    expect(file_get_contents(resource_path('views/admin/visits/workflow/ipd/_clinical-feed.blade.php')))
        ->toContain('_shared._prescription-panel');
    expect(file_get_contents(resource_path('views/admin/visits/workflow/emergency/_layout.blade.php')))
        ->toContain('_shared._prescription-panel');
});

it('queues visit prescriptions in house for POS with the visit patient automatically', function () {
    $this->post(route('visits.prescription', $this->visit), [
        'medicines' => [
            ['medicine_id' => $this->medicine->id, 'quantity' => 2],
        ],
    ])->assertRedirect()->assertSessionHas('success');

    $prescription = Prescription::with('items')->first();

    expect($prescription->fulfillment_type)->toBe('in_house')
        ->and($prescription->status)->toBe('pending')
        ->and($prescription->patient_id)->toBe($this->visit->patient_id)
        ->and(Prescription::inHousePending()->count())->toBe(1)
        ->and((float) $prescription->total_amount)->toBe(100.0)
        ->and((float) $prescription->items->first()->unit_price)->toBe(50.0)
        ->and((float) $prescription->items->first()->total_price)->toBe(100.0);
});

it('ignores posted external fulfillment so the prescription still reaches POS', function () {
    $this->post(route('visits.prescription', $this->visit), [
        'fulfillment_type' => 'external',
        'medicines' => [
            ['medicine_id' => $this->medicine->id, 'quantity' => 2],
        ],
    ])->assertRedirect()->assertSessionHas('success');

    $prescription = Prescription::first();

    expect($prescription->fulfillment_type)->toBe('in_house')
        ->and($prescription->status)->toBe('pending')
        ->and($prescription->patient_id)->toBe($this->patient->id);
});
