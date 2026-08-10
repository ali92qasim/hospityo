<?php

use App\Models\Admission;
use App\Models\Bed;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Visit Schema User',
        'email' => 'visit-schema@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('edit visits', 'web');
    Permission::findOrCreate('create visits', 'web');
    Permission::findOrCreate('view visits', 'web');
    $this->user->givePermissionTo(['edit visits', 'create visits', 'view visits']);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Schema Patient',
        'gender' => 'male',
        'age' => 35,
        'phone' => '03001112233',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03004445566',
        'emergency_relation' => 'Spouse',
    ]);

    $department = Department::create(['name' => 'Medicine', 'code' => 'MED', 'status' => 'active']);

    $this->doctor = Doctor::create([
        'name' => 'Dr. Schema',
        'doctor_no' => 'DOC-SCH-001',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03006667788',
        'email' => 'dr-schema@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1500,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);
});

it('stores OPD visit with spine fields and generated visit number', function () {
    $this->post(route('visits.store'), [
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now()->toDateTimeString(),
    ])->assertRedirect();

    $visit = Visit::latest('id')->first();

    expect($visit)->not->toBeNull()
        ->and($visit->visit_type)->toBe('opd')
        ->and($visit->status)->toBe('registered')
        ->and($visit->visit_no)->toStartWith('OPD');
});

it('assign doctor writes doctor_id on spine', function () {
    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
    ]);

    $this->post(route('visits.assign-doctor', $visit), [
        'doctor_id' => $this->doctor->id,
    ])->assertRedirect();

    $visit->refresh();

    expect($visit->doctor_id)->toBe($this->doctor->id)
        ->and($visit->status)->toBe('with_doctor');
});

it('IPD admit does not require spine doctor_id', function () {
    $ward = Ward::create([
        'name' => 'General Ward',
        'department_id' => Department::first()->id,
        'capacity' => 10,
        'ward_type' => 'general',
        'status' => 'active',
    ]);

    $bed = Bed::create([
        'ward_id' => $ward->id,
        'bed_number' => 'GW-01',
        'bed_type' => 'general',
        'daily_rate' => 2000,
        'status' => 'available',
    ]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'ipd',
        'visit_datetime' => now(),
        'status' => 'registered',
        'doctor_id' => null,
    ]);

    $this->post(route('visits.admit', $visit), [
        'bed_id' => $bed->id,
        'admission_notes' => 'Test admission',
    ])->assertRedirect();

    $visit->refresh();

    expect($visit->doctor_id)->toBeNull()
        ->and($visit->status)->toBe('admitted')
        ->and($visit->admission)->toBeInstanceOf(Admission::class);
});
