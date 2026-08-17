<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\ImagingOrder;
use App\Models\ImagingStudy;
use App\Models\LabOrder;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Visit Order User',
        'email' => 'visit-orders@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('edit visits', 'web');
    $this->user->givePermissionTo(['edit visits']);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Visit Order Patient',
        'gender' => 'female',
        'age' => 29,
        'phone' => '03001110001',
        'emergency_name' => 'Kin',
        'emergency_phone' => '03001110002',
        'emergency_relation' => 'Spouse',
    ]);

    $this->doctor = Doctor::create([
        'name' => 'Dr Visit Orders',
        'doctor_no' => 'DOC-VIS-ORD',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03001110003',
        'email' => 'visit-orders-doctor@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => Department::create(['name' => 'Visit Orders', 'code' => 'VORD', 'status' => 'active'])->id,
    ]);

    $this->visit = Visit::create([
        'visit_no' => 'VIS-ORD-001',
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'department_id' => $this->doctor->department_id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now(),
    ]);
});

it('visit lab section creates lab_orders and imaging section creates imaging_orders', function () {
    $labTest = LabTest::create([
        'code' => 'CBC-VIS',
        'name' => 'CBC Visit',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 400,
        'is_active' => true,
    ]);

    $imagingStudy = ImagingStudy::create([
        'code' => 'CXR-VIS',
        'name' => 'Chest X-Ray Visit',
        'category' => 'x-ray',
        'price' => 1200,
        'is_active' => true,
    ]);

    $this->post(route('visits.order-multiple-lab-tests', $this->visit), [
        'tests' => [
            [
                'lab_test_id' => $labTest->id,
                'quantity' => 1,
                'priority' => 'routine',
            ],
        ],
    ])->assertRedirect()->assertSessionHas('success');

    $this->post(route('visits.order-multiple-imaging-studies', $this->visit), [
        'tests' => [
            [
                'imaging_study_id' => $imagingStudy->id,
                'quantity' => 1,
                'priority' => 'routine',
            ],
        ],
    ])->assertRedirect()->assertSessionHas('success');

    expect(LabOrder::query()->where('visit_id', $this->visit->id)->count())->toBe(1)
        ->and(ImagingOrder::query()->where('visit_id', $this->visit->id)->count())->toBe(1)
        ->and(LabOrder::query()->where('visit_id', $this->visit->id)->first()->items()->first()->lab_test_id)->toBe($labTest->id)
        ->and(ImagingOrder::query()->where('visit_id', $this->visit->id)->first()->items()->first()->imaging_study_id)->toBe($imagingStudy->id);
});
