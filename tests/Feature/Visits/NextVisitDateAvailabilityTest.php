<?php

use App\Models\Consultation;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    config(['visits.dual_write_enabled' => false]);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    Carbon::setTestNow(Carbon::parse('2026-08-27 10:00:00'));

    Permission::findOrCreate('view visits', 'web');
    Permission::findOrCreate('edit visits', 'web');

    $this->user = User::create([
        'name' => 'Visit Clerk',
        'email' => 'visit-clerk-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->user->givePermissionTo(['view visits', 'edit visits']);
    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Follow Up Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03001112222',
    ]);

    $department = Department::create([
        'name' => 'General Medicine',
        'status' => 'active',
    ]);

    $this->doctor = Doctor::create([
        'name' => 'Subhan Doctor',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03003334444',
        'email' => 'subhan-doctor-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 6,
        'consultation_fee' => 1000,
        'available_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday'],
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $this->visit = Visit::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'with_doctor',
    ]);

    OpdVisit::create(['visit_id' => $this->visit->id, 'queue_priority' => 'medium']);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('rejects a next visit date when the assigned doctor is not available that weekday', function () {
    $this->post(route('visits.consultation', $this->visit), [
        'next_visit_date' => '2026-08-30',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors('next_visit_date');

    expect(Consultation::count())->toBe(0);
});

it('saves a next visit date when the assigned doctor is available that weekday', function () {
    $this->post(route('visits.consultation', $this->visit), [
        'next_visit_date' => '2026-08-31',
    ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Consultation::first()?->next_visit_date?->format('Y-m-d'))->toBe('2026-08-31');
});

it('visit workflow exposes the assigned doctor schedule for next visit date filtering', function () {
    $this->get(route('visits.workflow', $this->visit))
        ->assertOk()
        ->assertSee('id="assigned-doctor-schedule"', false)
        ->assertSee('id="next-visit-date"', false)
        ->assertSee('"available_days"', false)
        ->assertSee('Monday', false);
});

it('next visit date picker uses shared doctor weekday availability', function () {
    $js = file_get_contents(resource_path('js/visits-form.js'));

    expect($js)->toContain('isDoctorListedForDatetime')
        ->and($js)->toContain('assigned-doctor-schedule')
        ->and($js)->toContain('The selected doctor is not available on this day.');
});
