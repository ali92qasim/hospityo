<?php

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    Carbon::setTestNow(Carbon::parse('2026-08-27 10:00:00'));

    Permission::findOrCreate('view appointments', 'web');
    Permission::findOrCreate('create appointments', 'web');
    Permission::findOrCreate('edit appointments', 'web');
    Permission::findOrCreate('delete appointments', 'web');

    $this->user = User::create([
        'name' => 'Appointment Clerk',
        'email' => 'appointment-clerk-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->user->givePermissionTo([
        'view appointments',
        'create appointments',
        'edit appointments',
        'delete appointments',
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Booking Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03001112222',
    ]);

    $department = Department::create([
        'name' => 'Cardiology',
        'status' => 'active',
    ]);

    $this->doctor = Doctor::create([
        'name' => 'Dr. Schedule',
        'specialization' => 'Cardiology',
        'qualification' => 'MBBS',
        'phone' => '03003334444',
        'email' => 'dr-schedule-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 8,
        'consultation_fee' => 1500,
        'available_days' => ['Monday'],
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

function appointmentPayload(array $overrides = []): array
{
    return array_merge([
        'patient_id' => test()->patient->id,
        'doctor_id' => test()->doctor->id,
        'appointment_datetime' => '2026-08-31 10:00',
        'reason' => 'Follow up',
        'notes' => 'Bring reports',
    ], $overrides);
}

it('rejects booking an appointment on a past date', function () {
    $this->postJson(route('appointments.store'), appointmentPayload([
        'appointment_datetime' => '2026-08-26 10:00',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('appointment_datetime');

    expect(Appointment::count())->toBe(0);
});

it('rejects booking when the doctor has no available days', function () {
    $this->doctor->update(['available_days' => []]);

    $this->postJson(route('appointments.store'), appointmentPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('appointment_datetime')
        ->assertJsonFragment(['The selected doctor has no available days scheduled.']);

    expect(Appointment::count())->toBe(0);
});

it('rejects booking on a day the doctor is not scheduled', function () {
    $this->postJson(route('appointments.store'), appointmentPayload([
        'appointment_datetime' => '2026-08-27 11:00',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('appointment_datetime')
        ->assertJsonFragment(['The selected doctor is not available on this day.']);

    expect(Appointment::count())->toBe(0);
});

it('rejects booking outside the doctor shift hours', function () {
    $this->postJson(route('appointments.store'), appointmentPayload([
        'appointment_datetime' => '2026-08-31 08:00',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('appointment_datetime')
        ->assertJsonFragment(["The selected time is outside the doctor's availability (09:00 to 17:00)."]);

    expect(Appointment::count())->toBe(0);
});

it('creates an appointment on an available day inside the doctor shift', function () {
    $this->postJson(route('appointments.store'), appointmentPayload([
        'appointment_datetime' => '2026-08-31 09:00',
    ]))
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect(Appointment::count())->toBe(1)
        ->and(Appointment::first()->appointment_datetime->format('Y-m-d H:i'))->toBe('2026-08-31 09:00');
});

it('allows updating status and notes on a past appointment', function () {
    $appointment = Appointment::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'appointment_datetime' => '2026-08-20 10:00:00',
        'status' => 'scheduled',
        'notes' => 'Original note',
    ]);

    $this->putJson(route('appointments.update', $appointment), [
        'status' => 'completed',
        'notes' => 'Patient arrived late',
    ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect($appointment->fresh()->status)->toBe('completed')
        ->and($appointment->fresh()->notes)->toBe('Patient arrived late')
        ->and($appointment->fresh()->appointment_datetime->format('Y-m-d H:i'))->toBe('2026-08-20 10:00');
});

it('create appointment calendar uses client validation hooks', function () {
    $this->get(route('appointments.create'))
        ->assertOk()
        ->assertSee('id="appointmentForm"', false)
        ->assertSee('novalidate', false)
        ->assertSee('data-landmark="appointment-form"', false)
        ->assertSee('id="doctor-schedules-data"', false)
        ->assertSee('id="open-appointment-modal"', false)
        ->assertDontSee('onclick="openAppointmentModal()"', false);
});

it('appointment client validator uses just-validate and doctor schedule checks', function () {
    $js = file_get_contents(resource_path('js/appointments-validation.js'));

    expect($js)->toContain("from 'just-validate'")
        ->and($js)->toContain('submitFormAutomatically: false')
        ->and($js)->toContain('The selected doctor has no available days scheduled.')
        ->and($js)->toContain('Appointments cannot be booked on a past date.')
        ->and($js)->toContain("The selected time is outside the doctor's availability");
});

it('does not list a sunday-only doctor when booking on friday', function () {
    exec('node '.escapeshellarg(base_path('tests/js/available-doctor-ids.mjs')), $output, $code);

    expect($code)->toBe(0, implode("\n", $output));
});

it('schedule appointment modal defaults datetime to now and filters doctors by available days', function () {
    $js = file_get_contents(resource_path('js/appointments-calendar.js'));
    $open = strpos($js, 'function openAppointmentModal');
    $close = strpos($js, 'function closeAppointmentModal');
    $openBody = substr($js, $open, $close - $open);

    expect($openBody)->toContain('formatLocalDateTime(new Date())')
        ->and($openBody)->not->toContain('window.flatpickrInstance.clear()')
        ->and($openBody)->toContain('filterDoctorOptions()');
});

it('rebuilds the booking doctor dropdown so unavailable doctors are not listed', function () {
    $js = file_get_contents(resource_path('js/appointments-calendar.js'));
    $filter = strpos($js, 'function filterDoctorOptions');
    $next = strpos($js, 'function escapeHtml');
    $body = substr($js, $filter, $next - $filter);

    expect($js)->toContain('availableDoctorIds')
        ->and($body)->toContain('$allDoctorOptions')
        ->and(strpos($body, "select2('destroy')"))->toBeLessThan(strpos($body, '$doctorSelect.empty()'));
});

it('calendar events include appointment details for tooltips', function () {
    $appointment = Appointment::create(appointmentPayload([
        'appointment_datetime' => '2026-08-31 10:00:00',
        'status' => 'scheduled',
        'reason' => 'Follow up',
    ]));

    $this->getJson(route('calendar.events'))
        ->assertOk()
        ->assertJsonFragment([
            'id' => $appointment->id,
            'patient' => 'Booking Patient',
            'doctor' => 'Dr. Schedule',
            'status' => 'scheduled',
            'reason' => 'Follow up',
        ]);
});

it('appointment calendar shows pointer on dates and tooltips on booked events', function () {
    $css = file_get_contents(resource_path('css/appointments-calendar.css'));
    $js = file_get_contents(resource_path('js/appointments-calendar.js'));

    expect($css)
        ->toContain('.fc-daygrid-day:not(.fc-day-past)')
        ->and($css)->toContain('.appointment-event-tooltip')
        ->and($js)->toContain('appointment-event-tooltip')
        ->and($js)->toContain('eventMouseEnter')
        ->and($js)->toContain('eventMouseLeave');
});

it('appointment modal does not close when the overlay is clicked', function () {
    $js = file_get_contents(resource_path('js/appointments-calendar.js'));

    expect($js)
        ->toContain("$('.js-close-appointment-modal').on('click'")
        ->and($js)->not->toContain("$('#appointmentModal').on('click'")
        ->and($js)->toContain("if (e.key === 'Escape'");
});

it('rejects changing the datetime of a past appointment', function () {
    $appointment = Appointment::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'appointment_datetime' => '2026-08-20 10:00:00',
        'status' => 'scheduled',
    ]);

    $this->putJson(route('appointments.update', $appointment), [
        'appointment_datetime' => '2026-08-31 10:00',
        'status' => 'scheduled',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('appointment_datetime');

    expect($appointment->fresh()->appointment_datetime->format('Y-m-d H:i'))->toBe('2026-08-20 10:00');
});
