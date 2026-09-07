<?php

use App\Models\Admission;
use App\Models\Bed;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\ImagingOrder;
use App\Models\ImagingOrderItem;
use App\Models\ImagingStudy;
use App\Models\InventoryTransaction;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabTest;
use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Models\Patient;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use App\Services\IpdDraftBillService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
});

function ungatedTenant(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function ungatedPlanTenant(array $modules): Tenant
{
    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'multitenancy.switch_tenant_tasks' => [],
    ]);

    app('db')->purge('landlord');

    test()->artisan('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);

    $plan = Plan::create([
        'slug' => 'ungated-plan-'.uniqid(),
        'name' => 'Ungated Plan Fixture',
        'price' => 0,
        'billing_cycle' => 'monthly',
        'modules' => $modules,
        'is_active' => true,
    ]);

    $key = 'ungated-plan-'.uniqid();
    $tenant = Tenant::create([
        'name' => 'Ungated Plan Clinic',
        'slug' => $key,
        'domain' => $key.'.test',
        'database' => ':memory:',
        'status' => 'active',
        'plan_id' => $plan->id,
    ]);

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function ungatedUser(array $permissions, array $roles = []): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Ungated User',
        'email' => 'ungated-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    foreach ($roles as $roleName) {
        $user->assignRole(Role::findOrCreate($roleName, 'web'));
    }

    return $user;
}

function ungatedPatient(): Patient
{
    return Patient::create([
        'name' => 'Ungated Patient',
        'gender' => 'female',
        'age' => 30,
        'phone' => '0300'.random_int(1000000, 9999999),
        'emergency_name' => 'Kin',
        'emergency_phone' => '03001112233',
        'emergency_relation' => 'Spouse',
    ]);
}

function ungatedDoctor(): Doctor
{
    $department = Department::create([
        'name' => 'Ungated Dept '.uniqid(),
        'code' => 'U'.substr(uniqid(), -4),
        'status' => 'active',
    ]);

    return Doctor::create([
        'name' => 'Dr Ungated',
        'doctor_no' => 'DOC-U-'.uniqid(),
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005550000',
        'email' => 'dr-u-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 4,
        'consultation_fee' => 800,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);
}

function ungatedOpdVisit(Patient $patient, Doctor $doctor): Visit
{
    return Visit::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'department_id' => $doctor->department_id,
        'visit_type' => 'opd',
        'status' => 'with_doctor',
        'visit_datetime' => now(),
    ]);
}

function ungatedAdmittedIpdVisit(Patient $patient): Visit
{
    $department = Department::create([
        'name' => 'IPD Ungated '.uniqid(),
        'code' => 'I'.substr(uniqid(), -4),
        'status' => 'active',
    ]);

    $ward = Ward::create([
        'name' => 'Ungated Ward',
        'department_id' => $department->id,
        'capacity' => 5,
        'ward_type' => 'general',
        'status' => 'active',
    ]);

    $bed = Bed::create([
        'ward_id' => $ward->id,
        'bed_number' => 'U-'.substr(uniqid(), -4),
        'bed_type' => 'general',
        'daily_rate' => 2500,
        'status' => 'available',
    ]);

    $visit = Visit::create([
        'patient_id' => $patient->id,
        'visit_type' => 'ipd',
        'visit_datetime' => now(),
        'status' => 'admitted',
        'doctor_id' => null,
    ]);

    Admission::create([
        'visit_id' => $visit->id,
        'bed_id' => $bed->id,
        'admission_date' => now(),
        'status' => 'active',
    ]);

    $bed->update(['status' => 'occupied']);

    IpdDraftBillService::ensureForVisit($visit);

    return $visit;
}

it('patients-index visit register script reads module data attributes', function () {
    $script = file_get_contents(resource_path('js/patients-index.js'));

    expect($script)
        ->toContain('dataset.allowVisits')
        ->toContain('dataset.allowEmergency')
        ->toContain('Register OPD visit and open workflow')
        ->toContain('Register Emergency visit and open workflow');
});

it('compiled patients-index bundle gates emergency when a vite build is present', function () {
    $manifestPath = public_path('build/manifest.json');

    if (! file_exists($manifestPath)) {
        test()->markTestSkipped('No Vite build present');
    }

    $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
    $file = $manifest['resources/js/patients-index.js']['file'] ?? null;

    expect($file)->not->toBeNull();

    $bundle = file_get_contents(public_path('build/'.$file));

    expect($bundle)
        ->toContain('allowEmergency')
        ->toContain('allowVisits')
        ->not->toMatch('/\$\{s\("emergency","fa-ambulance"/');
});

it('omits patients-index visit register flags when visits and emergency are not entitled', function () {
    ungatedTenant(['patients']);
    $this->actingAs(ungatedUser(['view patients', 'create visits']));

    $this->get(route('patients.index'))
        ->assertOk()
        ->assertSee('data-can-create-visits="1"', false)
        ->assertDontSee('data-allow-visits="1"', false)
        ->assertDontSee('data-allow-emergency="1"', false);
});

it('shows patients-index visit register flags when visits and emergency are entitled', function () {
    ungatedTenant(['patients', 'visits', 'emergency']);
    $this->actingAs(ungatedUser(['view patients', 'create visits']));

    $this->get(route('patients.index'))
        ->assertOk()
        ->assertSee('data-can-create-visits="1"', false)
        ->assertSee('data-allow-visits="1"', false)
        ->assertSee('data-allow-emergency="1"', false);
});

it('omits data-allow-emergency on patients-index for a real plan that excludes emergency', function () {
    $tenant = ungatedPlanTenant(['patients', 'visits']);

    expect($tenant->hasModule('patients'))->toBeTrue()
        ->and($tenant->hasModule('visits'))->toBeTrue()
        ->and($tenant->hasModule('emergency'))->toBeFalse();

    $this->actingAs(ungatedUser(['view patients', 'create visits']));

    $html = $this->get(route('patients.index'))
        ->assertOk()
        ->assertSee('id="patients-index"', false)
        ->assertSee('data-allow-visits="1"', false)
        ->assertDontSee('data-allow-emergency="1"', false)
        ->assertDontSee('data-allow-emergency="0"', false)
        ->getContent();

    expect($html)->not->toContain('Register Emergency visit and open workflow');
});

it('omits admin dashboard module tiles and actions when those modules are not entitled', function () {
    ungatedTenant([]);
    $this->actingAs(ungatedUser([
        'view patients',
        'create patients',
        'view visits',
        'create visits',
        'view appointments',
        'create appointments',
        'view departments',
    ]));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('id="add-visit-btn"', false)
        ->assertDontSee('id="schedule-appointment-btn"', false)
        ->assertDontSee('id="view-history-btn"', false)
        ->assertDontSee('id="add-patient-btn"', false)
        ->assertDontSee('Register a new patient')
        ->assertDontSee('Manage patient records')
        ->assertDontSee('Book new appointment')
        ->assertDontSee('data-dashboard-tile="appointments"', false)
        ->assertDontSee('data-dashboard-tile="departments"', false)
        ->assertDontSee('data-dashboard-tile="emergency"', false);
});

it('shows admin dashboard module tiles and actions when those modules are entitled', function () {
    ungatedTenant(['visits', 'appointments', 'patients', 'departments', 'emergency']);
    $this->actingAs(ungatedUser([
        'view patients',
        'create patients',
        'view visits',
        'create visits',
        'view appointments',
        'create appointments',
        'view departments',
    ]));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('id="add-visit-btn"', false)
        ->assertSee('id="schedule-appointment-btn"', false)
        ->assertSee('id="view-history-btn"', false)
        ->assertSee('id="add-patient-btn"', false)
        ->assertSee('Register a new patient')
        ->assertSee('Manage patient records')
        ->assertSee('Book new appointment')
        ->assertSee('data-dashboard-tile="appointments"', false)
        ->assertSee('data-dashboard-tile="departments"', false)
        ->assertSee('data-dashboard-tile="emergency"', false);
});

it('omits doctor dashboard module tiles when those modules are not entitled', function () {
    ungatedTenant([]);
    $this->actingAs(ungatedUser([
        'view appointments',
        'view visits',
        'view wards',
    ], ['Doctor']));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('My Visits')
        ->assertDontSee('data-dashboard-tile="appointments"', false)
        ->assertDontSee('data-dashboard-tile="visits"', false)
        ->assertDontSee('data-dashboard-tile="ipd"', false)
        ->assertDontSee('data-dashboard-tile="emergency"', false);
});

it('shows doctor dashboard module tiles when those modules are entitled', function () {
    ungatedTenant(['appointments', 'visits', 'ipd', 'emergency']);
    $this->actingAs(ungatedUser([
        'view appointments',
        'view visits',
        'view wards',
    ], ['Doctor']));

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-dashboard-tile="appointments"', false)
        ->assertSee('data-dashboard-tile="visits"', false)
        ->assertSee('data-dashboard-tile="ipd"', false)
        ->assertSee('data-dashboard-tile="emergency"', false)
        ->assertSee('My Appointments')
        ->assertSee('OPD Visits')
        ->assertSee('IPD Visits')
        ->assertSee('Emergency Cases');
});

it('hides the near-expiry banner when pharmacy is not entitled', function () {
    ungatedTenant([]);
    $user = ungatedUser(['view pharmacy'], ['Hospital Administrator']);
    $this->actingAs($user);

    $tab = Unit::create([
        'name' => 'TAB/CAP',
        'abbreviation' => 'TAB',
        'conversion_factor' => 1,
        'type' => 'solid',
        'is_active' => true,
    ]);
    $category = MedicineCategory::create(['code' => 'UNG', 'name' => 'Ungated', 'is_active' => true]);
    $medicine = Medicine::create([
        'name' => 'Expiring Ungated Med',
        'sku' => 'UNG-EXP-1',
        'category_id' => $category->id,
        'base_unit_id' => $tab->id,
        'dispensing_unit_id' => $tab->id,
        'manage_stock' => true,
        'status' => 'active',
        'selling_price' => 25,
    ]);
    InventoryTransaction::create([
        'medicine_id' => $medicine->id,
        'type' => 'stock_in',
        'quantity' => 10,
        'remaining_quantity' => 10,
        'unit_cost' => 15,
        'total_cost' => 150,
        'batch_no' => 'UNG-EXP',
        'expiry_date' => now()->addMonths(3),
        'created_by' => $user->id,
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('expiring within 6 months');
});

it('shows the near-expiry banner when pharmacy is entitled', function () {
    ungatedTenant(['pharmacy']);
    $user = ungatedUser(['view pharmacy'], ['Hospital Administrator']);
    $this->actingAs($user);

    $tab = Unit::create([
        'name' => 'TAB/CAP',
        'abbreviation' => 'TAB',
        'conversion_factor' => 1,
        'type' => 'solid',
        'is_active' => true,
    ]);
    $category = MedicineCategory::create(['code' => 'UNG', 'name' => 'Ungated', 'is_active' => true]);
    $medicine = Medicine::create([
        'name' => 'Expiring Ungated Med',
        'sku' => 'UNG-EXP-1',
        'category_id' => $category->id,
        'base_unit_id' => $tab->id,
        'dispensing_unit_id' => $tab->id,
        'manage_stock' => true,
        'status' => 'active',
        'selling_price' => 25,
    ]);
    InventoryTransaction::create([
        'medicine_id' => $medicine->id,
        'type' => 'stock_in',
        'quantity' => 10,
        'remaining_quantity' => 10,
        'unit_cost' => 15,
        'total_cost' => 150,
        'batch_no' => 'UNG-EXP',
        'expiry_date' => now()->addMonths(3),
        'created_by' => $user->id,
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('expiring within 6 months');
});

it('omits ipd bill links when billing is not entitled', function () {
    ungatedTenant(['ipd']);
    $this->actingAs(ungatedUser(['view visits', 'view bills']));

    $visit = ungatedAdmittedIpdVisit(ungatedPatient());

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertDontSee('View Draft Bill')
        ->assertDontSee('Add Charges')
        ->assertDontSee('Print Draft')
        ->assertDontSee('Interim Invoice');
});

it('shows ipd bill links when billing is entitled', function () {
    ungatedTenant(['ipd', 'billing']);
    $this->actingAs(ungatedUser(['view visits', 'view bills']));

    $visit = ungatedAdmittedIpdVisit(ungatedPatient());

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertSee('View Draft Bill')
        ->assertSee('Add Charges')
        ->assertSee('Print Draft')
        ->assertSee('Interim Invoice');
});

it('omits investigation result links when laboratory and imaging are not entitled', function () {
    ungatedTenant(['visits']);
    $this->actingAs(ungatedUser([
        'view visits',
        'view lab orders',
        'create lab results',
        'view radiology results',
        'create radiology results',
    ]));

    $doctor = ungatedDoctor();
    $visit = ungatedOpdVisit(ungatedPatient(), $doctor);

    $labTest = LabTest::create([
        'code' => 'CBC-UNG',
        'name' => 'CBC Ungated',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 400,
        'is_active' => true,
    ]);
    $labOrder = LabOrder::create([
        'patient_id' => $visit->patient_id,
        'visit_id' => $visit->id,
        'doctor_id' => $doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);
    LabOrderItem::create([
        'lab_order_id' => $labOrder->id,
        'lab_test_id' => $labTest->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'ordered',
    ]);

    $study = ImagingStudy::create([
        'code' => 'CXR-UNG',
        'name' => 'Chest X-Ray Ungated',
        'category' => 'x-ray',
        'price' => 1200,
        'is_active' => true,
    ]);
    $imagingOrder = ImagingOrder::create([
        'patient_id' => $visit->patient_id,
        'visit_id' => $visit->id,
        'doctor_id' => $doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);
    ImagingOrderItem::create([
        'imaging_order_id' => $imagingOrder->id,
        'imaging_study_id' => $study->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'ordered',
    ]);

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertDontSee('Enter Lab Result')
        ->assertDontSee('Enter Imaging Report');
});

it('shows investigation result links when laboratory and imaging are entitled', function () {
    ungatedTenant(['visits', 'laboratory', 'imaging']);
    $this->actingAs(ungatedUser([
        'view visits',
        'view lab orders',
        'create lab results',
        'view radiology results',
        'create radiology results',
    ]));

    $doctor = ungatedDoctor();
    $visit = ungatedOpdVisit(ungatedPatient(), $doctor);

    $labTest = LabTest::create([
        'code' => 'CBC-UNG',
        'name' => 'CBC Ungated',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 400,
        'is_active' => true,
    ]);
    $labOrder = LabOrder::create([
        'patient_id' => $visit->patient_id,
        'visit_id' => $visit->id,
        'doctor_id' => $doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);
    LabOrderItem::create([
        'lab_order_id' => $labOrder->id,
        'lab_test_id' => $labTest->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'ordered',
    ]);

    $study = ImagingStudy::create([
        'code' => 'CXR-UNG',
        'name' => 'Chest X-Ray Ungated',
        'category' => 'x-ray',
        'price' => 1200,
        'is_active' => true,
    ]);
    $imagingOrder = ImagingOrder::create([
        'patient_id' => $visit->patient_id,
        'visit_id' => $visit->id,
        'doctor_id' => $doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);
    ImagingOrderItem::create([
        'imaging_order_id' => $imagingOrder->id,
        'imaging_study_id' => $study->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'ordered',
    ]);

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertSee('Enter Lab Result')
        ->assertSee('Enter Imaging Report');
});

it('omits investigation result links when the user lacks laboratory and imaging permissions', function () {
    ungatedTenant(['visits', 'laboratory', 'imaging']);
    $this->actingAs(ungatedUser(['view visits']));

    $doctor = ungatedDoctor();
    $visit = ungatedOpdVisit(ungatedPatient(), $doctor);

    $labTest = LabTest::create([
        'code' => 'CBC-UNG-RBAC',
        'name' => 'CBC Ungated Rbac',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 400,
        'is_active' => true,
    ]);
    $labOrder = LabOrder::create([
        'patient_id' => $visit->patient_id,
        'visit_id' => $visit->id,
        'doctor_id' => $doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);
    LabOrderItem::create([
        'lab_order_id' => $labOrder->id,
        'lab_test_id' => $labTest->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'ordered',
    ]);

    $study = ImagingStudy::create([
        'code' => 'CXR-UNG-RBAC',
        'name' => 'Chest X-Ray Ungated Rbac',
        'category' => 'x-ray',
        'price' => 1200,
        'is_active' => true,
    ]);
    $imagingOrder = ImagingOrder::create([
        'patient_id' => $visit->patient_id,
        'visit_id' => $visit->id,
        'doctor_id' => $doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);
    ImagingOrderItem::create([
        'imaging_order_id' => $imagingOrder->id,
        'imaging_study_id' => $study->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'ordered',
    ]);

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertSee('CBC Ungated Rbac')
        ->assertSee('Chest X-Ray Ungated Rbac')
        ->assertDontSee('Enter Lab Result')
        ->assertDontSee('Enter Imaging Report');
});

it('orders-list blades gate lab and imaging result links with allows', function () {
    $contents = file_get_contents(resource_path('views/admin/shared/diagnostics/_orders-list.blade.php'));

    expect($contents)
        ->toContain("allows(\\App\\Models\\Tenant::current(), auth()->user(), 'laboratory')")
        ->toContain("allows(\\App\\Models\\Tenant::current(), auth()->user(), 'imaging')");
});
