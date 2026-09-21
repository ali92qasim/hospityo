<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\LabReportRosterDoctor;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach (['view lab results', 'edit lab results'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::create([
        'name' => 'Lab Verifier',
        'email' => 'lab-reviewer-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo(['view lab results', 'edit lab results']);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Reviewer Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03001110000',
    ]);

    $department = Department::create([
        'name' => 'Reviewer Dept',
        'code' => 'RVD'.uniqid(),
        'status' => 'active',
    ]);

    $this->orderingDoctor = Doctor::create([
        'name' => 'Dr Ordering',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005550001',
        'email' => 'ordering-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $this->doctorA = Doctor::create([
        'name' => 'Dr Reviewer A',
        'specialization' => 'Pathology',
        'qualification' => 'MBBS, FCPS',
        'phone' => '03005550002',
        'email' => 'reviewer-a-'.uniqid().'@example.com',
        'gender' => 'female',
        'experience_years' => 8,
        'consultation_fee' => 1500,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $this->doctorB = Doctor::create([
        'name' => 'Dr Reviewer B',
        'specialization' => 'Hematology',
        'qualification' => 'MBBS',
        'phone' => '03005550003',
        'email' => 'reviewer-b-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 6,
        'consultation_fee' => 1200,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $this->order = LabOrder::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->orderingDoctor->id,
        'priority' => 'routine',
        'status' => 'completed',
        'ordered_at' => now(),
    ]);

    $this->labResult = LabResult::create([
        'lab_order_id' => $this->order->id,
        'results' => [],
        'status' => 'preliminary',
        'technician_id' => $this->user->id,
        'tested_at' => now(),
    ]);
});

it('stores selected reviewer doctors on verify and keeps pathologist as acting user', function () {
    LabReportRosterDoctor::create(['doctor_id' => $this->doctorA->id, 'sort_order' => 0]);
    LabReportRosterDoctor::create(['doctor_id' => $this->doctorB->id, 'sort_order' => 1]);

    $this->post(route('lab-results.verify', $this->labResult), [
        'reviewer_doctor_ids' => [$this->doctorA->id, $this->doctorB->id],
    ])->assertRedirect();

    $labResult = $this->labResult->fresh();

    expect($labResult->pathologist_id)->toBe($this->user->id)
        ->and($labResult->status)->toBe('final')
        ->and($labResult->reviewers()->orderByPivot('sort_order')->pluck('doctors.id')->all())
        ->toBe([$this->doctorA->id, $this->doctorB->id]);

    $pivotOrders = DB::table('lab_result_reviewers')
        ->where('lab_result_id', $labResult->id)
        ->orderBy('sort_order')
        ->pluck('sort_order')
        ->all();

    expect($pivotOrders)->toBe([0, 1]);
});

it('allows verify with zero reviewers', function () {
    $this->post(route('lab-results.verify', $this->labResult), [])
        ->assertRedirect();

    $labResult = $this->labResult->fresh();

    expect($labResult->reviewers)->toHaveCount(0)
        ->and($labResult->status)->toBe('final')
        ->and($labResult->pathologist_id)->toBe($this->user->id);
});

it('leaves reviewers empty so report chrome can omit consultant footer', function () {
    // Full Blade footer chrome lands in Tasks 5/9 — model state is the contract here.
    $this->post(route('lab-results.verify', $this->labResult), [])
        ->assertRedirect();

    expect($this->labResult->fresh()->reviewers)->toHaveCount(0);
});

it('ignores non-roster doctor ids on verify', function () {
    // Prefer silent filter: sync intersection only (not 422).
    LabReportRosterDoctor::create(['doctor_id' => $this->doctorA->id, 'sort_order' => 0]);

    $this->post(route('lab-results.verify', $this->labResult), [
        'reviewer_doctor_ids' => [$this->doctorA->id, $this->doctorB->id],
    ])->assertRedirect();

    expect($this->labResult->fresh()->reviewers()->pluck('doctors.id')->all())
        ->toBe([$this->doctorA->id]);
});

it('shows roster doctors on verify form and empty-roster message when none configured', function () {
    LabReportRosterDoctor::create(['doctor_id' => $this->doctorA->id, 'sort_order' => 0]);

    $this->get(route('lab-results.show', $this->labResult))
        ->assertOk()
        ->assertSee('name="reviewer_doctor_ids[]"', false)
        ->assertSee((string) $this->doctorA->id, false)
        ->assertDontSee('No consultants configured in Lab Report Print settings', false);

    LabReportRosterDoctor::query()->delete();

    $this->get(route('lab-results.show', $this->labResult))
        ->assertOk()
        ->assertSee('No consultants configured in Lab Report Print settings', false)
        ->assertDontSee('name="reviewer_doctor_ids[]"', false);
});
