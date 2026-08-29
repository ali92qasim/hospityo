<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\PrescriptionPrintTemplate;

function makePrintDoctor(string $email): Doctor
{
    $department = Department::query()->first()
        ?? Department::create(['name' => 'OPD', 'code' => 'OPD-PT', 'status' => 'active']);

    return Doctor::create([
        'name' => 'Resolution Doctor',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03001112233',
        'email' => $email,
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);
}

function makeTemplate(array $overrides = []): PrescriptionPrintTemplate
{
    return PrescriptionPrintTemplate::create(array_merge([
        'doctor_id' => null,
        'name' => 'Clinic default',
        'mode' => 'overlay_physical',
        'paper_size' => 'A4',
        'orientation' => 'portrait',
        'background_image_path' => null,
        'is_active' => true,
        'rx_start_y' => 80,
        'rx_row_height' => 8,
        'rx_max_rows' => 10,
        'rx_overflow_policy' => 'second_page_plain',
    ], $overrides));
}

it('resolves a doctor-specific active template over the clinic default', function () {
    $doctor = makePrintDoctor('res-doc-'.uniqid().'@example.com');
    makeTemplate(['name' => 'Clinic', 'doctor_id' => null, 'is_active' => true]);
    $doctorTemplate = makeTemplate([
        'name' => 'Doctor sheet',
        'doctor_id' => $doctor->id,
        'is_active' => true,
    ]);

    $resolved = PrescriptionPrintTemplate::resolvePrintTemplate($doctor->id);

    expect($resolved?->id)->toBe($doctorTemplate->id);
});

it('falls back to the clinic-wide active template when the doctor has none', function () {
    $doctor = makePrintDoctor('res-doc2-'.uniqid().'@example.com');
    $clinic = makeTemplate(['name' => 'Clinic', 'doctor_id' => null, 'is_active' => true]);
    makeTemplate([
        'name' => 'Other doctor',
        'doctor_id' => makePrintDoctor('other-'.uniqid().'@example.com')->id,
        'is_active' => true,
    ]);

    expect(PrescriptionPrintTemplate::resolvePrintTemplate($doctor->id)?->id)->toBe($clinic->id);
});

it('returns null when no active template exists', function () {
    makeTemplate(['is_active' => false]);

    expect(PrescriptionPrintTemplate::resolvePrintTemplate(null))->toBeNull();
});

it('never resolves an inactive doctor template even if it is the only doctor match', function () {
    $doctor = makePrintDoctor('inactive-'.uniqid().'@example.com');
    makeTemplate(['doctor_id' => $doctor->id, 'is_active' => false, 'name' => 'Off']);
    $clinic = makeTemplate(['doctor_id' => null, 'is_active' => true, 'name' => 'Clinic']);

    expect(PrescriptionPrintTemplate::resolvePrintTemplate($doctor->id)?->id)->toBe($clinic->id);
});
