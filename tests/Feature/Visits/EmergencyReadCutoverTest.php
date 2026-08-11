<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\EmergencyVisit;
use App\Models\Patient;
use App\Models\Triage;
use App\Models\User;
use App\Models\Visit;
use App\Workflows\Handlers\EmergencyVisitHandler;

beforeEach(function () {
    config(['visits.dual_write_enabled' => false]);

    $this->patient = Patient::create([
        'name' => 'Emergency Read Patient',
        'gender' => 'male',
        'age' => 50,
        'phone' => '03008880021',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03008880022',
        'emergency_relation' => 'Sibling',
    ]);
});

it('resolves emergency child detail when read flag is on', function () {
    config(['visits.read_from_child.emergency' => true]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'emergency',
        'visit_datetime' => now(),
        'status' => 'registered',
    ]);

    EmergencyVisit::create(['visit_id' => $visit->id]);

    $detail = $visit->fresh(['emergencyDetails'])->typeDetailForRead();

    expect($detail)->toBeInstanceOf(EmergencyVisit::class)
        ->and($visit->readsTypeDetailFromChild())->toBeTrue();
});

it('returns null type detail when emergency read flag is off', function () {
    config(['visits.read_from_child.emergency' => false]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'emergency',
        'visit_datetime' => now(),
        'status' => 'registered',
    ]);

    EmergencyVisit::create(['visit_id' => $visit->id]);

    expect($visit->fresh()->typeDetailForRead())->toBeNull()
        ->and($visit->readsTypeDetailFromChild())->toBeFalse();
});

it('uses spine doctor for emergency assigned doctor', function () {
    $department = Department::create(['name' => 'ER', 'code' => 'ER-READ', 'status' => 'active']);

    $doctor = Doctor::create([
        'name' => 'Dr. Emergency',
        'doctor_no' => 'DOC-EMR-READ',
        'department_id' => $department->id,
        'specialization' => 'Emergency',
        'qualification' => 'MBBS',
        'phone' => '03008880023',
        'email' => 'dr-emr-read@example.com',
        'gender' => 'female',
        'experience_years' => 8,
        'consultation_fee' => 2000,
        'shift_start' => '08:00:00',
        'shift_end' => '20:00:00',
        'status' => 'active',
    ]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'emergency',
        'visit_datetime' => now(),
        'status' => 'with_doctor',
        'doctor_id' => $doctor->id,
    ]);

    expect($visit->assignedDoctor()?->id)->toBe($doctor->id);
});

it('reads triage from triages relation in handler workflow data', function () {
    config(['visits.read_from_child.emergency' => true]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'emergency',
        'visit_datetime' => now(),
        'status' => 'triaged',
    ]);

    EmergencyVisit::create(['visit_id' => $visit->id]);

    Triage::create([
        'visit_id' => $visit->id,
        'priority_level' => 'urgent',
        'chief_complaint' => 'Chest pain',
        'pain_scale' => 7,
        'triaged_by' => User::create([
            'name' => 'Triage Nurse',
            'email' => 'triage-nurse@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ])->id,
        'triaged_at' => now(),
    ]);

    $data = (new EmergencyVisitHandler)->workflowData($visit->fresh(['emergencyDetails', 'triage']));

    expect($data['triage_completed'])->toBeTrue()
        ->and($data['triage_priority_level'])->toBe('urgent')
        ->and($data['has_emergency_detail'])->toBeTrue();
});
