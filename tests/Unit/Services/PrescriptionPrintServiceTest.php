<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PrescriptionPrintField;
use App\Models\PrescriptionPrintTemplate;
use App\Models\Visit;

function prescriptionPdfVisit(string $suffix): array
{
    $department = Department::create([
        'name' => 'PDF Department '.$suffix,
        'code' => 'PDF-'.$suffix,
        'status' => 'active',
    ]);
    $doctor = Doctor::create([
        'name' => 'PDF Doctor '.$suffix,
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03001110000',
        'email' => 'pdf-'.$suffix.'-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 3,
        'consultation_fee' => 500,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);
    $patient = Patient::create([
        'name' => 'PDF Patient '.$suffix,
        'gender' => 'female',
        'age' => 30,
        'phone' => '03002223344',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03004445566',
        'emergency_relation' => 'Spouse',
    ]);
    $visit = Visit::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now(),
    ]);

    return [$doctor, $visit];
}

function activePrescriptionPdfTemplate(Doctor $doctor, ?string $backgroundPath = null): PrescriptionPrintTemplate
{
    $template = PrescriptionPrintTemplate::create([
        'doctor_id' => $doctor->id,
        'name' => 'Active PDF Template',
        'mode' => 'overlay_physical',
        'paper_size' => 'A4',
        'orientation' => 'portrait',
        'background_image_path' => $backgroundPath,
        'is_active' => true,
        'rx_start_y' => 80,
        'rx_row_height' => 10,
        'rx_max_rows' => 10,
        'rx_overflow_policy' => 'second_page_plain',
    ]);
    PrescriptionPrintField::create([
        'template_id' => $template->id,
        'field_key' => 'patient_name',
        'x_mm' => 20,
        'y_mm' => 20,
        'font_size' => 11,
        'font_weight' => 'normal',
        'align' => 'left',
        'visible' => true,
    ]);

    return $template;
}

it('returns null when no print template is configured', function () {
    $department = Department::create(['name' => 'OPD', 'code' => 'OPD-PDF', 'status' => 'active']);
    $doctor = Doctor::create([
        'name' => 'No Template',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03001110000',
        'email' => 'no-template-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 3,
        'consultation_fee' => 500,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);
    $patient = Patient::create([
        'name' => 'No Template Patient',
        'gender' => 'female',
        'age' => 30,
        'phone' => '03002223344',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03004445566',
        'emergency_relation' => 'Spouse',
    ]);
    $visit = Visit::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now(),
    ]);

    expect(app(\App\Services\PrescriptionPrintService::class)->renderPrescriptionPdf($visit))->toBeNull();
});

it('renders an active complete template as a PDF string', function () {
    [$doctor, $visit] = prescriptionPdfVisit('ACTIVE');
    activePrescriptionPdfTemplate($doctor);

    $pdf = app(\App\Services\PrescriptionPrintService::class)->renderPrescriptionPdf($visit);

    expect($pdf)->toBeString()
        ->and($pdf)->toStartWith('%PDF');
});

it('renders overlay mode without printing its reference background', function () {
    [$doctor, $visit] = prescriptionPdfVisit('OVERLAY');
    activePrescriptionPdfTemplate($doctor, 'tenants/demo/reference-only.png');

    $pdf = app(\App\Services\PrescriptionPrintService::class)->renderPrescriptionPdf($visit);

    expect($pdf)->toBeString()
        ->and($pdf)->toStartWith('%PDF');
});

it('returns null when a digitized template background file is missing', function () {
    [$doctor, $visit] = prescriptionPdfVisit('MISSING-BACKGROUND');
    $template = activePrescriptionPdfTemplate(
        $doctor,
        'tenants/demo/missing-digitized-background.png'
    );
    $template->update(['mode' => 'digitized_background']);

    expect(app(\App\Services\PrescriptionPrintService::class)->renderPrescriptionPdf($visit))
        ->toBeNull();
});

it('renders a calibration sheet as a PDF string', function () {
    [$doctor] = prescriptionPdfVisit('CALIBRATION');
    $template = activePrescriptionPdfTemplate($doctor);

    $pdf = app(\App\Services\PrescriptionPrintService::class)->renderCalibrationPdf($template);

    expect($pdf)->toStartWith('%PDF');
});
