<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\ImagingOrder;
use App\Models\ImagingOrderItem;
use App\Models\ImagingStudy;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Visit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $tenant = new Tenant;
    $tenant->id = 1;
    $tenant->status = 'active';
    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    Permission::findOrCreate('create radiology results', 'web');

    $this->user = User::create([
        'name' => 'Radiology Create User',
        'email' => 'rad-create-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo('create radiology results');

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Radiology Patient',
        'gender' => 'female',
        'age' => 32,
        'phone' => '03001112233',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03001112234',
        'emergency_relation' => 'Spouse',
    ]);

    $department = Department::create(['name' => 'Radiology', 'code' => 'RAD', 'status' => 'active']);

    $this->doctor = Doctor::create([
        'name' => 'Dr. Radiology',
        'doctor_no' => 'DOC-RAD-001',
        'specialization' => 'Radiology',
        'qualification' => 'MBBS',
        'phone' => '03001112235',
        'email' => 'dr-rad@example.com',
        'gender' => 'male',
        'experience_years' => 8,
        'consultation_fee' => 1500,
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

    $this->study = ImagingStudy::create([
        'code' => 'CXR-001',
        'name' => 'Chest X-Ray',
        'category' => 'x-ray',
        'price' => 1200,
        'is_active' => true,
    ]);

    $this->imagingOrder = ImagingOrder::create([
        'patient_id' => $this->patient->id,
        'visit_id' => $this->visit->id,
        'doctor_id' => $this->doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);

    ImagingOrderItem::create([
        'imaging_order_id' => $this->imagingOrder->id,
        'imaging_study_id' => $this->study->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'ordered',
    ]);
});

it('loads radiology result create page for imaging orders', function () {
    $this->get(route('radiology-results.create', $this->imagingOrder))
        ->assertOk()
        ->assertSee('Add X ray Result')
        ->assertSee('Chest X-Ray')
        ->assertSee($this->patient->name)
        ->assertSee('rich-text-editor', false)
        ->assertSee('radiology-results-form', false);
});

it('loads radiology result create page when imaging order has no study linked', function () {
    $emptyOrder = ImagingOrder::create([
        'patient_id' => $this->patient->id,
        'visit_id' => $this->visit->id,
        'doctor_id' => $this->doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);

    $this->get(route('radiology-results.create', $emptyOrder))
        ->assertOk()
        ->assertSee('Add Imaging Result')
        ->assertSee('Unknown Test');
});
