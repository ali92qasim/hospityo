<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PrescriptionPrintTemplate;
use App\Models\User;
use App\Models\Visit;
use App\Services\PrescriptionPrintTemplateService;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Visit Template Print User',
        'email' => 'visit-template-print@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('view visits', 'web');
    $this->user->givePermissionTo('view visits');

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Template Print Patient',
        'gender' => 'female',
        'age' => 28,
        'phone' => '03001112233',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03004445566',
        'emergency_relation' => 'Spouse',
    ]);

    $department = Department::create([
        'name' => 'Template Print OPD',
        'code' => 'TP-OPD',
        'status' => 'active',
    ]);

    $this->doctor = Doctor::create([
        'name' => 'Dr. Template Print',
        'doctor_no' => 'DOC-TEMPLATE-PRINT',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03006667788',
        'email' => 'dr-template-print@example.com',
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

    $this->activateDoctorTemplate = function (): PrescriptionPrintTemplate {
        $template = PrescriptionPrintTemplate::create([
            'doctor_id' => $this->doctor->id,
            'name' => 'Doctor Visit Print',
            'mode' => 'overlay_physical',
            'paper_size' => 'A4',
            'orientation' => 'portrait',
            'is_active' => false,
            'rx_start_y' => 80,
            'rx_row_height' => 8,
            'rx_max_rows' => 10,
            'rx_overflow_policy' => 'cap_with_note',
        ]);

        $service = new PrescriptionPrintTemplateService;
        $service->seedDefaultFields($template);
        $service->activate($template);

        return $template->fresh();
    };
});

it('renders the existing HTML letterhead when no template is configured', function () {
    $response = $this->get(route('visits.print', $this->visit));

    $response->assertOk();
    $response->assertSee('Print Prescription', false);
    $response->assertSee($this->patient->name, false);
    expect($response->headers->get('content-type'))->not->toContain('application/pdf');
});

it('streams a PDF when an active complete template exists for the doctor', function () {
    ($this->activateDoctorTemplate)();

    $response = $this->get(route('visits.print', $this->visit));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('does not use the PDF interceptor for IPD visits', function () {
    $this->visit->update(['visit_type' => 'ipd']);
    ($this->activateDoctorTemplate)();

    $response = $this->get(route('visits.print', $this->visit));

    $response->assertOk();
    $response->assertSee('IPD Report', false);
    expect($response->headers->get('content-type'))->not->toContain('application/pdf');
});
