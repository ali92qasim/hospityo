<?php

use App\Support\PrescriptionPrintFieldCatalog;

it('includes every core activation field', function () {
    expect(PrescriptionPrintFieldCatalog::keys())->toContain(
        'patient_name',
        'age',
        'date',
        'doctor_name',
    );
});

it('lists core keys used to decide completeness', function () {
    expect(PrescriptionPrintFieldCatalog::coreKeys())->toBe([
        'patient_name',
        'age',
        'date',
        'doctor_name',
    ]);
});

it('includes the variable fields currently shown on the HTML letterhead', function () {
    $keys = PrescriptionPrintFieldCatalog::keys();

    expect($keys)->toContain(
        'hospital_name',
        'hospital_address',
        'hospital_phone',
        'hospital_email',
        'doctor_qualification',
        'doctor_specialization',
        'doctor_registration_no',
        'age_gender',
        'patient_phone',
        'patient_no',
        'visit_no',
        'gender',
        'diagnosis',
        'allergies',
        'presenting_complaints',
        'patient_history',
        'investigations',
        'vital_bp',
        'vital_temp',
        'vital_pulse',
        'vital_spo2',
        'vital_bsr',
        'vital_weight',
        'vital_height',
        'gpe_chest',
        'gpe_abdomen',
        'gpe_cvs',
        'gpe_cns',
        'gpe_pupils',
        'gpe_conjunctiva',
        'gpe_nails',
        'gpe_throat',
        'gpe_sclera',
        'gpe_gcs',
        'instructions',
        'next_visit_date',
    );
});
