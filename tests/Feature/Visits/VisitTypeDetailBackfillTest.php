<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Backfill User',
        'email' => 'backfill@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('create visits', 'web');
    $this->user->givePermissionTo(['create visits']);
    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Backfill Patient',
        'gender' => 'male',
        'age' => 33,
        'phone' => '03005550001',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03005550002',
        'emergency_relation' => 'Sibling',
    ]);

    $department = Department::create(['name' => 'Medicine', 'code' => 'MED-BF', 'status' => 'active']);

    $this->doctor = Doctor::create([
        'name' => 'Dr. Backfill',
        'doctor_no' => 'DOC-BF-001',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005550003',
        'email' => 'dr-backfill@example.com',
        'gender' => 'male',
        'experience_years' => 4,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);
});

it('backfill maps spine priority to opd queue_priority', function () {
    config(['visits.dual_write_enabled' => false]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
        'doctor_id' => $this->doctor->id,
        'priority' => 'high',
    ]);

    $this->artisan('visits:backfill-type-details', ['type' => 'opd'])
        ->assertSuccessful();

    expect(OpdVisit::where('visit_id', $visit->id)->value('queue_priority'))->toBe('high');
});

it('verify reports zero mismatches after backfill', function () {
    config(['visits.dual_write_enabled' => false]);

    Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
        'priority' => 'medium',
    ]);

    Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'ipd',
        'visit_datetime' => now(),
        'status' => 'registered',
    ]);

    $this->artisan('visits:backfill-type-details')->assertSuccessful();

    $this->artisan('visits:backfill-type-details', ['--verify' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('0 mismatches');
});
