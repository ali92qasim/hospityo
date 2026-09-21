<?php

use App\Models\LabOrder;
use App\Models\Patient;
use Illuminate\Support\Facades\Route;

it('does not register lab-results.public-report', function () {
    expect(Route::has('lab-results.public-report'))->toBeFalse();
});

it('still registers token public lab report routes', function () {
    expect(Route::has('lab-report.show'))->toBeTrue()
        ->and(Route::has('lab-report.verify'))->toBeTrue()
        ->and(Route::has('lab-report.view'))->toBeTrue();
});

it('publicReportUrl still points at the token show route', function () {
    $patient = Patient::create([
        'name' => 'Share Patient',
        'gender' => 'male',
        'age' => 30,
        'phone' => '03001112233',
    ]);

    $department = \App\Models\Department::create([
        'name' => 'Share Dept',
        'code' => 'SHR',
        'status' => 'active',
    ]);

    $doctor = \App\Models\Doctor::create([
        'name' => 'Dr Share',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005556666',
        'email' => 'share-doc-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $order = LabOrder::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'priority' => 'routine',
        'status' => 'completed',
        'ordered_at' => now(),
    ]);

    expect($order->publicReportUrl())->toContain($order->ensureShareToken())
        ->and($order->publicReportUrl())->toContain('/lab-report/');
});
