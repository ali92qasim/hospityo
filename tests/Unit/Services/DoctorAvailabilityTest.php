<?php

use App\Models\Doctor;
use App\Services\DoctorAvailability;
use Illuminate\Support\Carbon;

function makeScheduleDoctor(array $overrides = []): Doctor
{
    return new Doctor(array_merge([
        'available_days' => ['Monday'],
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
    ], $overrides));
}

it('rejects a doctor with no available days', function () {
    $service = new DoctorAvailability();
    $doctor = makeScheduleDoctor(['available_days' => []]);

    expect($service->isAvailableAt($doctor, Carbon::parse('2026-08-31 10:00')))->toBeFalse()
        ->and($service->failureReason($doctor, Carbon::parse('2026-08-31 10:00')))
        ->toBe('The selected doctor has no available days scheduled.');
});

it('rejects a doctor with null available days', function () {
    $service = new DoctorAvailability();
    $doctor = makeScheduleDoctor(['available_days' => null]);

    expect($service->failureReason($doctor, Carbon::parse('2026-08-31 10:00')))
        ->toBe('The selected doctor has no available days scheduled.');
});

it('rejects a weekday that is not on the doctor schedule', function () {
    $service = new DoctorAvailability();
    $doctor = makeScheduleDoctor();

    expect($service->failureReason($doctor, Carbon::parse('2026-08-27 10:00')))
        ->toBe('The selected doctor is not available on this day.');
});

it('rejects a time outside the doctor shift', function () {
    $service = new DoctorAvailability();
    $doctor = makeScheduleDoctor();

    expect($service->failureReason($doctor, Carbon::parse('2026-08-31 08:45')))
        ->toBe("The selected time is outside the doctor's availability (09:00 to 17:00).")
        ->and($service->failureReason($doctor, Carbon::parse('2026-08-31 17:15')))
        ->toBe("The selected time is outside the doctor's availability (09:00 to 17:00).");
});

it('accepts a datetime on an available day inside the shift including boundaries', function () {
    $service = new DoctorAvailability();
    $doctor = makeScheduleDoctor();

    expect($service->isAvailableAt($doctor, Carbon::parse('2026-08-31 09:00')))->toBeTrue()
        ->and($service->isAvailableAt($doctor, Carbon::parse('2026-08-31 17:00')))->toBeTrue()
        ->and($service->failureReason($doctor, Carbon::parse('2026-08-31 10:30')))->toBeNull();
});
