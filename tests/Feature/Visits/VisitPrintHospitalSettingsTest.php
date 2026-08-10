<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Visit Print User',
        'email' => 'visit-print@example.com',
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
        'name' => 'Print Patient',
        'gender' => 'female',
        'age' => 28,
        'phone' => '03001112233',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03004445566',
        'emergency_relation' => 'Spouse',
    ]);

    $department = Department::create(['name' => 'OPD', 'code' => 'OPD', 'status' => 'active']);

    $this->doctor = Doctor::create([
        'name' => 'Dr. Print',
        'doctor_no' => 'DOC-PRINT',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03006667788',
        'email' => 'dr-print@example.com',
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
});

it('loads hospital particulars on the visit report from the database', function () {
    Setting::query()->create(['key' => 'hospital_name', 'value' => 'City Care Hospital']);
    Setting::query()->create(['key' => 'hospital_address', 'value' => '12 Main Boulevard']);
    Setting::query()->create(['key' => 'hospital_phone', 'value' => '042-111-2222']);
    Setting::query()->create(['key' => 'hospital_email', 'value' => 'info@citycare.test']);

    Cache::forget('settings.hospital_name');
    Cache::forget('settings.hospital_address');
    Cache::forget('settings.hospital_phone');
    Cache::forget('settings.hospital_email');
    Cache::forget('settings.hospital_logo');

    $response = $this->get(route('visits.print', $this->visit));

    $response->assertOk();
    $response->assertSee('City Care Hospital', false);
    $response->assertSee('12 Main Boulevard', false);
    $response->assertSee('042-111-2222', false);
});
