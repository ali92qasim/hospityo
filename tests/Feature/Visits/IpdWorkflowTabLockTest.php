<?php

use App\Models\Admission;
use App\Models\Bed;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\IpdCareTeam;
use App\Models\IpdVisit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\VitalSign;
use App\Models\Ward;
use App\Workflows\Handlers\IpdVisitHandler;
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
        'name' => 'IPD Tab Lock User',
        'email' => 'ipd-tab-lock-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->user->givePermissionTo(['view visits']);
    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'IPD Tab Lock Patient',
        'gender' => 'male',
        'age' => 41,
        'phone' => '03008882222',
    ]);

    $this->department = Department::create([
        'name' => 'Medicine',
        'code' => 'MED-TAB-LOCK',
        'status' => 'active',
    ]);
});

function makeIpdVisitForTabLock(): Visit
{
    $visit = Visit::create([
        'patient_id' => test()->patient->id,
        'visit_type' => 'ipd',
        'visit_datetime' => now(),
        'status' => 'registered',
        'doctor_id' => null,
    ]);

    IpdVisit::create(['visit_id' => $visit->id]);

    return $visit;
}

function admitIpdVisitForTabLock(Visit $visit): Visit
{
    $ward = Ward::create([
        'name' => 'Lock Ward',
        'department_id' => test()->department->id,
        'capacity' => 4,
        'ward_type' => 'general',
        'status' => 'active',
    ]);

    $bed = Bed::create([
        'ward_id' => $ward->id,
        'bed_number' => 'L-01',
        'bed_type' => 'general',
        'daily_rate' => 2000,
        'status' => 'available',
    ]);

    Admission::create([
        'visit_id' => $visit->id,
        'bed_id' => $bed->id,
        'admission_date' => now(),
        'status' => 'active',
    ]);

    $bed->update(['status' => 'occupied']);
    $visit->update(['status' => 'admitted']);

    return $visit->fresh(['admission', 'allVitalSigns', 'careTeam', 'consultation']);
}

it('keeps later ipd tabs locked until admission is completed', function () {
    $visit = makeIpdVisitForTabLock();

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertSee('data-workflow-panel="admission"', false)
        ->assertDontSee('data-workflow-panel="admission" data-tab-locked="1"', false)
        ->assertSee('data-workflow-panel="vitals" data-tab-locked="1"', false)
        ->assertSee('data-workflow-panel="consultation" data-tab-locked="1"', false)
        ->assertSee('data-workflow-panel="gpe" data-tab-locked="1"', false)
        ->assertSee('data-workflow-panel="care-team" data-tab-locked="1"', false)
        ->assertSee('Complete the Admission step first.');
});

it('unlocks vitals after admission and keeps later clinical tabs locked without vitals', function () {
    $visit = admitIpdVisitForTabLock(makeIpdVisitForTabLock());

    $html = $this->get(route('visits.workflow', $visit))->assertOk()->getContent();

    expect($html)
        ->not->toContain('data-workflow-panel="vitals" data-tab-locked="1"')
        ->toContain('data-workflow-panel="consultation" data-tab-locked="1"')
        ->toContain('Record vital signs first.');
});

it('unlocks consultation after vitals and a care team doctor are present', function () {
    $visit = admitIpdVisitForTabLock(makeIpdVisitForTabLock());

    VitalSign::create([
        'visit_id' => $visit->id,
        'recorded_by' => $this->user->id,
        'temperature' => 98.6,
    ]);

    $doctor = Doctor::create([
        'name' => 'Dr. Lock',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03008883333',
        'email' => 'dr-lock-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 6,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $this->department->id,
    ]);

    IpdCareTeam::create([
        'visit_id' => $visit->id,
        'doctor_id' => $doctor->id,
        'is_primary' => true,
        'added_at' => now(),
        'added_by' => $this->user->id,
    ]);

    $html = $this->get(route('visits.workflow', $visit->fresh()))->assertOk()->getContent();

    expect($html)
        ->not->toContain('data-workflow-panel="consultation" data-tab-locked="1"')
        ->not->toContain('data-workflow-panel="gpe" data-tab-locked="1"');
});

it('handler tab access is driven by completed workflow steps', function () {
    $visit = makeIpdVisitForTabLock();
    $access = (new IpdVisitHandler)->workflowData($visit)['tab_access'];

    expect($access['admission']['unlocked'])->toBeTrue()
        ->and($access['vitals']['unlocked'])->toBeFalse()
        ->and($access['consultation']['unlocked'])->toBeFalse();

    $visit = admitIpdVisitForTabLock($visit);
    $access = (new IpdVisitHandler)->workflowData($visit)['tab_access'];

    expect($access['vitals']['unlocked'])->toBeTrue()
        ->and($access['consultation']['unlocked'])->toBeFalse();
});

it('workflow tab script blocks locked tabs and explains why', function () {
    $js = file_get_contents(resource_path('js/visit-workflow-ipd.js'));

    expect($js)->toContain('data-tab-locked')
        ->and($js)->toContain('Complete the previous step first.');
});
