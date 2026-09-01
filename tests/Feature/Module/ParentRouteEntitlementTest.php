<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorShareRule;
use App\Models\ImagingStudy;
use App\Models\LabTest;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Visit;
use App\Workflows\Handlers\EmergencyVisitHandler;
use App\Workflows\Handlers\IpdVisitHandler;
use App\Workflows\Handlers\OpdVisitHandler;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
});

function bindEntitlementTenant(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function entitlementUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Entitlement User',
        'email' => 'entitlement-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function entitlementPatient(): Patient
{
    return Patient::create([
        'name' => 'Entitlement Patient',
        'gender' => 'female',
        'age' => 30,
        'phone' => '0300'.random_int(1000000, 9999999),
        'emergency_name' => 'Kin',
        'emergency_phone' => '03001112233',
        'emergency_relation' => 'Spouse',
    ]);
}

function entitlementService(): Service
{
    return Service::create([
        'name' => 'Consult',
        'code' => 'CON-'.uniqid(),
        'category' => 'consultation',
        'price' => 500,
        'is_active' => true,
    ]);
}

function entitlementOpdVisit(Patient $patient): Visit
{
    $department = Department::create([
        'name' => 'Entitlement Dept '.uniqid(),
        'code' => 'E'.substr(uniqid(), -4),
        'status' => 'active',
    ]);

    $doctor = Doctor::create([
        'name' => 'Dr Entitlement',
        'doctor_no' => 'DOC-E-'.uniqid(),
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005550000',
        'email' => 'dr-e-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 4,
        'consultation_fee' => 800,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    return Visit::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'department_id' => $department->id,
        'visit_type' => 'opd',
        'status' => 'with_doctor',
        'visit_datetime' => now(),
    ]);
}

function billStorePayload(Patient $patient, Service $service, string $billType, array $itemExtra = []): array
{
    return [
        'patient_id' => $patient->id,
        'bill_date' => now()->toDateString(),
        'bill_type' => $billType,
        'items' => [[
            'service_id' => $itemExtra['service_id'] ?? $service->id,
            'lab_test_id' => $itemExtra['lab_test_id'] ?? null,
            'imaging_study_id' => $itemExtra['imaging_study_id'] ?? null,
            'description' => 'Line',
            'quantity' => 1,
            'unit_price' => 500,
        ]],
    ];
}

// ── 1. Bills bill_type ───────────────────────────────────────────────────────

it('rejects pharmacy bill_type when pharmacy module is not entitled', function () {
    bindEntitlementTenant(['billing']);
    $this->actingAs(entitlementUser(['create bills']));

    $this->post(route('bills.store'), billStorePayload(entitlementPatient(), entitlementService(), 'pharmacy'))
        ->assertForbidden();
});

it('rejects emergency bill_type when emergency module is not entitled', function () {
    bindEntitlementTenant(['billing']);
    $this->actingAs(entitlementUser(['create bills']));

    $this->post(route('bills.store'), billStorePayload(entitlementPatient(), entitlementService(), 'emergency'))
        ->assertForbidden();
});

it('rejects ipd bill_type when ipd module is not entitled', function () {
    bindEntitlementTenant(['billing']);
    $this->actingAs(entitlementUser(['create bills']));

    $this->post(route('bills.store'), billStorePayload(entitlementPatient(), entitlementService(), 'ipd'))
        ->assertForbidden();
});

it('allows pharmacy bill_type when pharmacy module is entitled', function () {
    bindEntitlementTenant(['billing', 'pharmacy']);
    $this->actingAs(entitlementUser(['create bills']));

    $this->post(route('bills.store'), billStorePayload(entitlementPatient(), entitlementService(), 'pharmacy'))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(\App\Models\Bill::where('bill_type', 'pharmacy')->exists())->toBeTrue();
});

it('hides unentitled bill_type options on the create form', function () {
    bindEntitlementTenant(['billing']);
    $this->actingAs(entitlementUser(['create bills']));

    $this->get(route('bills.create'))
        ->assertOk()
        ->assertSee('value="opd"', false)
        ->assertDontSee('value="pharmacy"', false)
        ->assertDontSee('value="emergency"', false)
        ->assertDontSee('value="ipd"', false);
});

// ── 2. Bill line item lab / imaging ──────────────────────────────────────────

it('rejects bill lab_test_id when laboratory module is not entitled', function () {
    bindEntitlementTenant(['billing']);
    $this->actingAs(entitlementUser(['create bills']));

    $labTest = LabTest::create([
        'code' => 'CBC-E',
        'name' => 'CBC',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 400,
        'is_active' => true,
    ]);

    $this->post(route('bills.store'), billStorePayload(
        entitlementPatient(),
        entitlementService(),
        'opd',
        ['service_id' => null, 'lab_test_id' => $labTest->id]
    ))->assertForbidden();
});

it('allows bill lab_test_id when laboratory module is entitled', function () {
    bindEntitlementTenant(['billing', 'laboratory']);
    $this->actingAs(entitlementUser(['create bills']));

    $labTest = LabTest::create([
        'code' => 'CBC-E2',
        'name' => 'CBC 2',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 400,
        'is_active' => true,
    ]);

    $this->post(route('bills.store'), billStorePayload(
        entitlementPatient(),
        entitlementService(),
        'opd',
        ['service_id' => null, 'lab_test_id' => $labTest->id]
    ))->assertRedirect()->assertSessionHas('success');
});

it('rejects bill imaging_study_id when imaging module is not entitled', function () {
    bindEntitlementTenant(['billing']);
    $this->actingAs(entitlementUser(['create bills']));

    $study = ImagingStudy::create([
        'code' => 'CXR-E',
        'name' => 'CXR',
        'category' => 'x-ray',
        'price' => 900,
        'is_active' => true,
    ]);

    $this->post(route('bills.store'), billStorePayload(
        entitlementPatient(),
        entitlementService(),
        'opd',
        ['service_id' => null, 'imaging_study_id' => $study->id]
    ))->assertForbidden();
});

it('hides lab and imaging item types when those modules are not entitled', function () {
    bindEntitlementTenant(['billing']);
    $this->actingAs(entitlementUser(['create bills']));

    $this->get(route('bills.create'))
        ->assertOk()
        ->assertDontSee('value="lab"', false)
        ->assertDontSee('value="imaging"', false);
});

// ── 3. Visit investigation orders ────────────────────────────────────────────

it('rejects visit lab orders when laboratory module is not entitled', function () {
    bindEntitlementTenant(['visits']);
    $this->actingAs(entitlementUser(['edit visits']));

    $visit = entitlementOpdVisit(entitlementPatient());
    $labTest = LabTest::create([
        'code' => 'CBC-V',
        'name' => 'CBC V',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 400,
        'is_active' => true,
    ]);

    $this->post(route('visits.order-multiple-lab-tests', $visit), [
        'tests' => [[
            'lab_test_id' => $labTest->id,
            'quantity' => 1,
            'priority' => 'routine',
        ]],
    ])->assertForbidden();
});

it('allows visit lab orders when laboratory module is entitled', function () {
    bindEntitlementTenant(['visits', 'laboratory']);
    $this->actingAs(entitlementUser(['edit visits']));

    $visit = entitlementOpdVisit(entitlementPatient());
    $labTest = LabTest::create([
        'code' => 'CBC-V2',
        'name' => 'CBC V2',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 400,
        'is_active' => true,
    ]);

    $this->post(route('visits.order-multiple-lab-tests', $visit), [
        'tests' => [[
            'lab_test_id' => $labTest->id,
            'quantity' => 1,
            'priority' => 'routine',
        ]],
    ])->assertRedirect()->assertSessionHas('success');
});

it('rejects visit imaging orders when imaging module is not entitled', function () {
    bindEntitlementTenant(['visits']);
    $this->actingAs(entitlementUser(['edit visits']));

    $visit = entitlementOpdVisit(entitlementPatient());
    $study = ImagingStudy::create([
        'code' => 'CXR-V',
        'name' => 'CXR V',
        'category' => 'x-ray',
        'price' => 900,
        'is_active' => true,
    ]);

    $this->post(route('visits.order-multiple-imaging-studies', $visit), [
        'tests' => [[
            'imaging_study_id' => $study->id,
            'quantity' => 1,
            'priority' => 'routine',
        ]],
    ])->assertForbidden();
});

it('gates opd and ipd investigation flags on laboratory and imaging modules', function () {
    bindEntitlementTenant(['visits', 'ipd']);
    $visit = entitlementOpdVisit(entitlementPatient());

    $opd = (new OpdVisitHandler)->workflowData($visit);
    expect($opd['show_lab_investigations'])->toBeFalse()
        ->and($opd['show_imaging_investigations'])->toBeFalse()
        ->and($opd['show_investigations'])->toBeFalse();

    $ipd = (new IpdVisitHandler)->workflowData($visit);
    expect($ipd['show_lab_investigations'])->toBeFalse()
        ->and($ipd['show_imaging_investigations'])->toBeFalse();

    bindEntitlementTenant(['visits', 'ipd', 'laboratory', 'imaging']);
    $opdOn = (new OpdVisitHandler)->workflowData($visit);
    expect($opdOn['show_lab_investigations'])->toBeTrue()
        ->and($opdOn['show_imaging_investigations'])->toBeTrue()
        ->and($opdOn['show_investigations'])->toBeTrue();
});

it('keeps emergency investigations hidden even when laboratory is entitled', function () {
    bindEntitlementTenant(['emergency', 'laboratory', 'imaging']);
    $visit = entitlementOpdVisit(entitlementPatient());
    $visit->update(['visit_type' => 'emergency']);

    $data = (new EmergencyVisitHandler)->workflowData($visit);
    expect($data['show_investigations'])->toBeFalse();
});

// ── 4. Visit prescriptions ───────────────────────────────────────────────────

it('rejects visit prescriptions when pharmacy module is not entitled', function () {
    bindEntitlementTenant(['visits']);
    $this->actingAs(entitlementUser(['edit visits']));

    $visit = entitlementOpdVisit(entitlementPatient());
    $medicine = Medicine::create([
        'name' => 'Para',
        'generic_name' => 'Paracetamol',
        'unit' => 'tablet',
        'strength' => '500mg',
        'status' => 'active',
        'selling_price' => 10,
        'manage_stock' => false,
    ]);

    $this->post(route('visits.prescription', $visit), [
        'medicines' => [['medicine_id' => $medicine->id, 'quantity' => 1]],
    ])->assertForbidden();
});

it('allows visit prescriptions when pharmacy module is entitled', function () {
    bindEntitlementTenant(['visits', 'pharmacy']);
    $this->actingAs(entitlementUser(['edit visits']));

    $visit = entitlementOpdVisit(entitlementPatient());
    $medicine = Medicine::create([
        'name' => 'Para 2',
        'generic_name' => 'Paracetamol',
        'unit' => 'tablet',
        'strength' => '500mg',
        'status' => 'active',
        'selling_price' => 10,
        'manage_stock' => false,
    ]);

    $this->post(route('visits.prescription', $visit), [
        'medicines' => [['medicine_id' => $medicine->id, 'quantity' => 1]],
    ])->assertRedirect()->assertSessionHas('success');
});

// ── 5. Taxes applies-to ──────────────────────────────────────────────────────

it('rejects tax bill_types for modules that are not entitled', function () {
    bindEntitlementTenant(['billing']);
    $this->actingAs(entitlementUser(['create bills']));

    $this->post(route('taxes.store'), [
        'name' => 'GST',
        'code' => 'GST1',
        'percentage' => 5,
        'bill_types' => ['pharmacy'],
    ])->assertForbidden();
});

it('allows entitled tax bill_types', function () {
    bindEntitlementTenant(['billing', 'pharmacy']);
    $this->actingAs(entitlementUser(['create bills']));

    $this->post(route('taxes.store'), [
        'name' => 'GST',
        'code' => 'GST2',
        'percentage' => 5,
        'bill_types' => ['pharmacy'],
    ])->assertRedirect()->assertSessionHas('success');
});

it('hides unentitled tax applies-to checkboxes', function () {
    bindEntitlementTenant(['billing']);
    $this->actingAs(entitlementUser(['create bills']));

    $this->get(route('taxes.create'))
        ->assertOk()
        ->assertDontSee('value="pharmacy"', false)
        ->assertDontSee('value="emergency"', false);
});

// ── 6. Doctor-share applies_to and report filters ────────────────────────────

it('rejects doctor-share applies_to lab when laboratory is not entitled', function () {
    bindEntitlementTenant(['doctor-share']);
    $this->actingAs(entitlementUser(['create share rules']));

    $this->post(route('doctor-share.rules.store'), [
        'investigation_scope' => 'all',
        'share_type' => 'percentage',
        'share_value' => 10,
        'applies_to' => 'lab',
    ])->assertForbidden();

    expect(DoctorShareRule::count())->toBe(0);
});

it('allows doctor-share applies_to lab when laboratory is entitled', function () {
    bindEntitlementTenant(['doctor-share', 'laboratory']);
    $this->actingAs(entitlementUser(['create share rules']));

    $this->post(route('doctor-share.rules.store'), [
        'investigation_scope' => 'all',
        'share_type' => 'percentage',
        'share_value' => 10,
        'applies_to' => 'lab',
    ])->assertRedirect()->assertSessionHas('success');
});

it('rejects doctor-share report bill_type emergency when emergency is not entitled', function () {
    bindEntitlementTenant(['doctor-share']);
    $this->actingAs(entitlementUser(['view share reports']));

    $this->get(route('doctor-share.reports.index', ['bill_type' => 'emergency']))
        ->assertForbidden();
});

it('allows doctor-share report bill_type emergency when emergency is entitled', function () {
    bindEntitlementTenant(['doctor-share', 'emergency']);
    $this->actingAs(entitlementUser(['view share reports']));

    $this->get(route('doctor-share.reports.index', ['bill_type' => 'emergency']))
        ->assertOk();
});

it('hides unentitled doctor-share applies_to options', function () {
    bindEntitlementTenant(['doctor-share']);
    $this->actingAs(entitlementUser(['create share rules']));

    $this->get(route('doctor-share.rules.create'))
        ->assertOk()
        ->assertSee('value="all"', false)
        ->assertDontSee('value="lab"', false)
        ->assertDontSee('value="emergency"', false);
});

// ── 7. Reports investigation test_type ───────────────────────────────────────

it('rejects investigation report test_type lab when laboratory is not entitled', function () {
    bindEntitlementTenant(['reports']);
    $this->actingAs(entitlementUser(['view reports']));

    $this->get(route('reports.lab-tests', ['test_type' => 'lab']))
        ->assertForbidden();
});

it('rejects investigation report test_type radiology when imaging is not entitled', function () {
    bindEntitlementTenant(['reports']);
    $this->actingAs(entitlementUser(['view reports']));

    $this->get(route('reports.lab-tests', ['test_type' => 'radiology']))
        ->assertForbidden();
});

it('allows investigation report test_type lab when laboratory is entitled', function () {
    bindEntitlementTenant(['reports', 'laboratory']);
    $this->actingAs(entitlementUser(['view reports']));

    $this->get(route('reports.lab-tests', ['test_type' => 'lab']))
        ->assertOk();
});

it('hides unentitled investigation report test_type options', function () {
    bindEntitlementTenant(['reports']);
    $this->actingAs(entitlementUser(['view reports']));

    $this->get(route('reports.lab-tests'))
        ->assertOk()
        ->assertDontSee('value="lab"', false)
        ->assertDontSee('value="radiology"', false);
});
