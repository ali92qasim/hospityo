<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabReportRosterDoctor;
use App\Models\LabResult;
use App\Models\LabResultItem;
use App\Models\LabTest;
use App\Models\LabTestParameter;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);
    $this->withoutVite();
    Cache::flush();

    Permission::findOrCreate('view lab results', 'web');

    $this->user = User::create([
        'name' => 'Chrome Printer',
        'email' => 'lab-chrome-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo('view lab results');
    $this->actingAs($this->user);

    Setting::set('hospital_name', 'Chrome City Hospital');
    Setting::set('hospital_address', '99 Lab Avenue');

    $this->patient = Patient::create([
        'name' => 'Chrome Patient',
        'gender' => 'female',
        'age' => 28,
        'phone' => '03009998877',
    ]);

    $department = Department::create([
        'name' => 'Chrome Dept',
        'code' => 'CHR'.uniqid(),
        'status' => 'active',
    ]);

    $this->doctor = Doctor::create([
        'name' => 'Dr Ordering Chrome',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005551111',
        'email' => 'ordering-chrome-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 4,
        'consultation_fee' => 900,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $this->reviewer = Doctor::create([
        'name' => 'Dr Review Chrome',
        'specialization' => 'Pathology',
        'qualification' => 'FCPS',
        'phone' => '03005552222',
        'email' => 'review-chrome-'.uniqid().'@example.com',
        'gender' => 'female',
        'experience_years' => 9,
        'consultation_fee' => 1500,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    LabReportRosterDoctor::create([
        'doctor_id' => $this->reviewer->id,
        'sort_order' => 0,
    ]);

    $this->visit = Visit::create([
        'visit_no' => 'VIS-CHR-001',
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'department_id' => $department->id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now()->subDay()->setTime(10, 30),
    ]);

    $this->order = LabOrder::create([
        'patient_id' => $this->patient->id,
        'visit_id' => $this->visit->id,
        'doctor_id' => $this->doctor->id,
        'priority' => 'routine',
        'status' => 'reported',
        'ordered_at' => now()->subHours(6),
        'sample_collected_at' => now()->subHours(4),
        'completed_at' => now()->subHour(),
        'clinical_notes' => 'Suspected anemia with fatigue; please correlate with CBC findings carefully.',
    ]);

    $labTest = LabTest::create([
        'code' => 'CBC',
        'name' => 'Complete Blood Count',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 800,
        'is_active' => true,
    ]);

    $parameter = LabTestParameter::create([
        'lab_test_id' => $labTest->id,
        'parameter_name' => 'Hemoglobin',
        'unit' => 'g/dL',
        'data_type' => 'numeric',
        'reference_ranges' => ['normal' => '12-16'],
        'display_order' => 1,
        'is_active' => true,
    ]);

    LabOrderItem::create([
        'lab_order_id' => $this->order->id,
        'lab_test_id' => $labTest->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'reported',
    ]);

    $this->labResult = LabResult::create([
        'lab_order_id' => $this->order->id,
        'results' => [],
        'status' => 'final',
        'technician_id' => $this->user->id,
        'pathologist_id' => $this->user->id,
        'tested_at' => now()->subHours(2),
        'verified_at' => now()->subHour(),
        'reported_at' => now()->subHour(),
    ]);

    $this->labResult->reviewers()->sync([
        $this->reviewer->id => ['sort_order' => 0],
    ]);

    LabResultItem::create([
        'lab_result_id' => $this->labResult->id,
        'lab_test_parameter_id' => $parameter->id,
        'value' => '11.2',
        'unit' => 'g/dL',
        'flag' => 'L',
        'entered_by' => $this->user->id,
        'entered_at' => now()->subHours(2),
    ]);

    $this->order->refresh();
});

it('shows registration location from hospital info and not visit_type', function () {
    $this->get(route('investigation-orders.report', $this->order))
        ->assertOk()
        ->assertSee('Registration Location:', false)
        ->assertSee('Chrome City Hospital, 99 Lab Avenue', false)
        ->assertSee('Case #:', false)
        ->assertSee($this->order->order_number, false)
        ->assertDontSee('Order #:', false)
        ->assertSee('Suspected anemia with fatigue', false)
        ->assertSee('Hematology', false)
        ->assertSee('Dr Review Chrome', false)
        ->assertSee('Registration Date:', false)
        ->assertDontSee('<span class="patient-label">Registration Location:</span>
                            <span>opd</span>', false)
        ->assertDontSee('<span class="patient-label">Registration Location:</span>
                            <span>OPD</span>', false);
});

it('omits consultant and note lines when empty', function () {
    $this->labResult->reviewers()->sync([]);
    $this->order->update(['clinical_notes' => null]);

    $this->get(route('investigation-orders.report', $this->order))
        ->assertOk()
        ->assertDontSee('<span class="patient-label">Consultant:</span>', false)
        ->assertDontSee('<span class="patient-label">Note:</span>', false)
        ->assertDontSee('>Pending</span>', false);
});
