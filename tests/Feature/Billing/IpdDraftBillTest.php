<?php

use App\Models\Bed;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\JournalEntry;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use App\Services\IpdDraftBillService;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'IPD Clerk',
        'email' => 'ipd-draft@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'IPD Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03009998888',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03007776666',
        'emergency_relation' => 'Brother',
    ]);

    $department = Department::create(['name' => 'Medicine', 'code' => 'MED', 'status' => 'active']);

    $this->doctor = Doctor::create([
        'name' => 'Dr. IPD',
        'doctor_no' => 'DOC-IPD',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005554444',
        'email' => 'dr-ipd@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $ward = Ward::create([
        'name' => 'General Ward',
        'department_id' => $department->id,
        'capacity' => 10,
        'ward_type' => 'general',
        'status' => 'active',
    ]);

    $this->bed = Bed::create([
        'ward_id' => $ward->id,
        'bed_number' => 'GW-01',
        'bed_type' => 'general',
        'daily_rate' => 1500,
        'status' => 'available',
    ]);

    $this->visit = Visit::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'visit_type' => 'ipd',
        'status' => 'registered',
        'visit_datetime' => now(),
    ]);
});

it('creates a draft ipd bill when ensuring for an ipd visit', function () {
    $bill = IpdDraftBillService::ensureForVisit($this->visit);

    expect($bill)->not->toBeNull()
        ->and($bill->status)->toBe('draft')
        ->and($bill->bill_type)->toBe('ipd')
        ->and($bill->visit_id)->toBe($this->visit->id)
        ->and($bill->patient_id)->toBe($this->patient->id)
        ->and((float) $bill->total_amount)->toBe(0.0);

    expect(JournalEntry::where('reference_type', 'Bill')->where('reference_id', $bill->id)->count())->toBe(0);
});

it('reuses the same draft bill for the same visit', function () {
    $first = IpdDraftBillService::ensureForVisit($this->visit);
    $second = IpdDraftBillService::ensureForVisit($this->visit);

    expect($second->id)->toBe($first->id)
        ->and($this->visit->bills()->where('status', 'draft')->count())->toBe(1);
});

it('does not create a draft bill for opd visits', function () {
    $opd = Visit::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'visit_type' => 'opd',
        'status' => 'registered',
        'visit_datetime' => now(),
    ]);

    expect(IpdDraftBillService::ensureForVisit($opd))->toBeNull();
});
