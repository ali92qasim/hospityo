<?php

use App\Models\Bed;
use App\Models\Department;
use App\Models\IpdVisit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    config([
        'visits.dual_write_enabled' => false,
        'visits.read_from_child.ipd' => true,
        'visits.workflow_accordion_ui' => true,
    ]);

    Permission::findOrCreate('view visits', 'web');

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->user = User::create([
        'name' => 'IPD Bed Empty User',
        'email' => 'ipd-bed-empty-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->user->givePermissionTo(['view visits']);
    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'IPD Empty Bed Patient',
        'gender' => 'female',
        'age' => 32,
        'phone' => '03008881111',
    ]);

    $this->department = Department::create([
        'name' => 'Medicine',
        'code' => 'MED-BED-EMPTY',
        'status' => 'active',
    ]);
});

function makeUnadmittedIpdVisit(Patient $patient): Visit
{
    $visit = Visit::create([
        'patient_id' => $patient->id,
        'visit_type' => 'ipd',
        'visit_datetime' => now(),
        'status' => 'registered',
        'doctor_id' => null,
    ]);

    IpdVisit::create(['visit_id' => $visit->id]);

    return $visit;
}

it('explains that beds appear after they are added when none are available', function () {
    $visit = makeUnadmittedIpdVisit($this->patient);

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertSee('Select Bed for Admission')
        ->assertSee('data-landmark="ipd-bed-empty-state"', false)
        ->assertSee('Beds will appear here once they are added to a ward.')
        ->assertDontSee('class="bed-card', false);
});

it('lists wards without available beds and explains the selected-ward empty state', function () {
    Ward::create([
        'name' => 'General Ward',
        'department_id' => $this->department->id,
        'capacity' => 4,
        'ward_type' => 'general',
        'status' => 'active',
    ]);

    $visit = makeUnadmittedIpdVisit($this->patient);

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertSee('General Ward')
        ->assertSee('data-landmark="ipd-bed-empty-state"', false)
        ->assertSee('Beds will appear here once they are added to a ward.');
});

it('hides the empty-state guidance when an available bed exists', function () {
    $ward = Ward::create([
        'name' => 'Surgical Ward',
        'department_id' => $this->department->id,
        'capacity' => 4,
        'ward_type' => 'general',
        'status' => 'active',
    ]);

    Bed::create([
        'bed_number' => 'S-101',
        'ward_id' => $ward->id,
        'bed_type' => 'general',
        'status' => 'available',
        'daily_rate' => 1500,
    ]);

    $visit = makeUnadmittedIpdVisit($this->patient);

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertSee('S-101')
        ->assertSee('Surgical Ward')
        ->assertSee('data-landmark="ipd-bed-empty-state"', false)
        ->assertSee('hidden', false);

    $html = $this->get(route('visits.workflow', $visit))->getContent();
    $emptyStart = strpos($html, 'data-landmark="ipd-bed-empty-state"');

    expect($emptyStart)->not->toBeFalse();
    expect(substr($html, $emptyStart, 220))->toContain('hidden');
});

it('ward filter toggles the bed empty-state for the selected ward', function () {
    $js = file_get_contents(resource_path('js/visit-workflow-admission.js'));

    expect($js)->toContain('ipd-bed-empty-state')
        ->and($js)->toContain('Beds will appear here once they are added to the selected ward.')
        ->and($js)->toContain('Beds will appear here once they are added to a ward.');
});
