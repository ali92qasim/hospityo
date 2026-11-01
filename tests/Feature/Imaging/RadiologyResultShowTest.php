<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\ImagingOrder;
use App\Models\ImagingOrderItem;
use App\Models\ImagingReport;
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

    Permission::findOrCreate('view radiology results', 'web');

    $this->user = User::create([
        'name' => 'Radiology Show User',
        'email' => 'rad-show-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo('view radiology results');

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Radiology Show Patient',
        'gender' => 'female',
        'age' => 41,
        'phone' => '03004445566',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03004445567',
        'emergency_relation' => 'Spouse',
    ]);

    $department = Department::create(['name' => 'Radiology', 'code' => 'RAD', 'status' => 'active']);

    $this->doctor = Doctor::create([
        'name' => 'Dr. Show Radiology',
        'doctor_no' => 'DOC-RAD-SHOW',
        'specialization' => 'Radiology',
        'qualification' => 'MBBS',
        'phone' => '03004445568',
        'email' => 'dr-rad-show@example.com',
        'gender' => 'male',
        'experience_years' => 8,
        'consultation_fee' => 1500,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);
});

function radiologyShowReport(?int $visitId, int $patientId, int $doctorId, int $userId): ImagingReport
{
    $order = ImagingOrder::create([
        'patient_id' => $patientId,
        'visit_id' => $visitId,
        'doctor_id' => $doctorId,
        'priority' => 'routine',
        'status' => 'completed',
        'ordered_at' => now(),
    ]);

    $study = ImagingStudy::create([
        'code' => 'CXR-'.uniqid(),
        'name' => 'Chest X-Ray',
        'category' => 'x-ray',
        'price' => 1200,
        'is_active' => true,
    ]);

    ImagingOrderItem::create([
        'imaging_order_id' => $order->id,
        'imaging_study_id' => $study->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'completed',
    ]);

    return ImagingReport::create([
        'imaging_order_id' => $order->id,
        'report_text' => 'No acute findings.',
        'status' => 'final',
        'radiologist_id' => $userId,
        'reported_at' => now(),
    ]);
}

it('shows a radiology result whose imaging order has no visit', function () {
    $report = radiologyShowReport(null, $this->patient->id, $this->doctor->id, $this->user->id);

    $this->get(route('radiology-results.show', $report))
        ->assertOk()
        ->assertSee('Radiology Show Patient')
        ->assertSeeInOrder(['Visit:', 'N/A'], false);
});

it('still shows the visit number when the imaging order has a visit', function () {
    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'with_doctor',
    ]);

    $report = radiologyShowReport($visit->id, $this->patient->id, $this->doctor->id, $this->user->id);

    $this->get(route('radiology-results.show', $report))
        ->assertOk()
        ->assertSee($visit->fresh()->visit_no);
});
