<?php

use App\Models\Admission;
use App\Models\Bed;
use App\Models\Department;
use App\Models\IpdVisit;
use App\Models\Patient;
use App\Models\Ward;
use App\Models\User;
use App\Models\Visit;
use App\Workflows\Handlers\IpdVisitHandler;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'IPD UI User',
        'email' => 'ipd-ui@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('view visits', 'web');
    $this->user->givePermissionTo(['view visits']);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'IPD UI Patient',
        'gender' => 'male',
        'age' => 45,
        'phone' => '03008880001',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03008880002',
        'emergency_relation' => 'Sibling',
    ]);
});

it('workflow blade has no ipd visit_type conditionals', function () {
    $content = file_get_contents(resource_path('views/admin/visits/workflow.blade.php'));

    expect($content)->not->toMatch("/visit_type\\s*===?\\s*['\"]ipd['\"]/");
});

it('renders ipd workflow with handler-driven ui flags before admission', function () {
    config([
        'visits.dual_write_enabled' => false,
        'visits.read_from_child.ipd' => true,
        'visits.workflow_accordion_ui' => true,
    ]);

    Department::create(['name' => 'Medicine', 'code' => 'MED-UI', 'status' => 'active']);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'ipd',
        'visit_datetime' => now(),
        'status' => 'registered',
        'doctor_id' => null,
    ]);

    IpdVisit::create(['visit_id' => $visit->id]);

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertSee('data-workflow-layout="ipd"', false)
        ->assertSee('data-landmark="ipd-workflow-layout"', false)
        ->assertSee('data-landmark="ipd-episode-sidebar"', false)
        ->assertSee('data-landmark="ipd-clinical-feed"', false)
        ->assertDontSee('data-landmark="opd-workflow-layout"', false)
        ->assertSee('Print IPD Report')
        ->assertSee('data-landmark="workflow-back-to-list"', false)
        ->assertSee('Back to Admitted Patients', false)
        ->assertSee('Select Bed for Admission')
        ->assertDontSee('Record Vital Signs');
});

it('renders ipd workflow accordion after admission', function () {
    config([
        'visits.dual_write_enabled' => false,
        'visits.read_from_child.ipd' => true,
        'visits.workflow_accordion_ui' => true,
    ]);

    $department = Department::create(['name' => 'Medicine', 'code' => 'MED-ACC', 'status' => 'active']);

    $ward = Ward::create([
        'name' => 'Accordion Ward',
        'department_id' => $department->id,
        'capacity' => 5,
        'ward_type' => 'general',
        'status' => 'active',
    ]);

    $bed = Bed::create([
        'ward_id' => $ward->id,
        'bed_number' => 'ACC-01',
        'bed_type' => 'general',
        'daily_rate' => 2500,
        'status' => 'available',
    ]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'ipd',
        'visit_datetime' => now(),
        'status' => 'admitted',
        'doctor_id' => null,
    ]);

    IpdVisit::create(['visit_id' => $visit->id]);

    Admission::create([
        'visit_id' => $visit->id,
        'bed_id' => $bed->id,
        'admission_date' => now(),
        'status' => 'active',
    ]);

    $bed->update(['status' => 'occupied']);

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertSee('data-workflow-accordion-root', false)
        ->assertSee('Record Vital Signs')
        ->assertSee('data-landmark="ipd-clinical-timeline"', false)
        ->assertSee('Clinical Timeline')
        ->assertDontSee('Vital Signs History');
});

function makeAdmittedIpdVisit(string $suffix): Visit
{
    $department = Department::create(['name' => 'Medicine', 'code' => 'MED-'.$suffix, 'status' => 'active']);

    $ward = Ward::create([
        'name' => 'Ward '.$suffix,
        'department_id' => $department->id,
        'capacity' => 5,
        'ward_type' => 'general',
        'status' => 'active',
    ]);

    $bed = Bed::create([
        'ward_id' => $ward->id,
        'bed_number' => $suffix.'-01',
        'bed_type' => 'general',
        'daily_rate' => 2500,
        'status' => 'available',
    ]);

    $visit = Visit::create([
        'patient_id' => test()->patient->id,
        'visit_type' => 'ipd',
        'visit_datetime' => now(),
        'status' => 'admitted',
        'doctor_id' => null,
    ]);

    IpdVisit::create(['visit_id' => $visit->id]);

    Admission::create([
        'visit_id' => $visit->id,
        'bed_id' => $bed->id,
        'admission_date' => now(),
        'status' => 'active',
    ]);

    $bed->update(['status' => 'occupied']);

    return $visit;
}

it('splits ipd investigations into lab and imaging tabs after admission', function () {
    config([
        'visits.dual_write_enabled' => false,
        'visits.read_from_child.ipd' => true,
        'visits.workflow_accordion_ui' => true,
    ]);

    $visit = makeAdmittedIpdVisit('LABIMG');

    $html = $this->get(route('visits.workflow', $visit))->assertOk()->getContent();

    expect($html)
        ->toContain('data-workflow-section="lab"')
        ->toContain('data-workflow-section="imaging"')
        ->toContain('data-workflow-panel="lab"')
        ->toContain('data-workflow-panel="imaging"')
        ->toContain('id="gpe-content"')
        ->toContain('id="lab-tab"')
        ->toContain('id="imaging-tab"')
        ->not->toContain('Order Investigations')
        ->not->toContain('data-workflow-section="tests"')
        ->not->toContain('id="gpe-tab-content"')
        ->not->toContain('aria-label="Admission workflow"')
        ->not->toContain('role="tablist"');

    expect(substr_count($html, "showTab('care-team')"))->toBe(1)
        ->and(substr_count($html, 'Manage Care Team'))->toBe(1);
});

it('places next visit date inside the consultation form before imaging', function () {
    $feed = file_get_contents(resource_path('views/admin/visits/workflow/ipd/_clinical-feed.blade.php'));
    $form = file_get_contents(resource_path('views/admin/visits/workflow/_shared/_consultation-form.blade.php'));
    $opd = file_get_contents(resource_path('views/admin/visits/workflow/opd/_layout.blade.php'));

    $gpeIncludePos = strpos($form, 'opd._consultation');
    $nextPos = strpos($form, 'id="next-visit-date"');
    $savePos = strpos($form, 'Save {{ $workflowData[\'consultation_label\']');

    expect($feed)->not->toContain('_next-visit-date')
        ->and($opd)->not->toContain('_next-visit-date')
        ->and($gpeIncludePos)->not->toBeFalse()
        ->and($nextPos)->not->toBeFalse()
        ->and($savePos)->not->toBeFalse()
        ->and($gpeIncludePos)->toBeLessThan($nextPos)
        ->and($nextPos)->toBeLessThan($savePos);
});

it('clones workflow investigation rows from a native template not a live select2 select', function () {
    $select2 = file_get_contents(resource_path('js/visit-workflow-select2.js'));
    $scripts = file_get_contents(resource_path('views/admin/visits/workflow/_shared/_scripts.blade.php'));
    $section = file_get_contents(resource_path('views/admin/visits/workflow/opd/_investigation-order-section.blade.php'));
    $accordion = file_get_contents(resource_path('views/components/workflow-accordion-section.blade.php'));

    expect($section)
        ->toContain('id="{{ $catalog }}-test-row-template"')
        ->toContain('investigation-item-select')
        ->and($scripts)->toContain('template.content.cloneNode')
        ->and($select2)->toContain('template.content.cloneNode')
        ->and($select2)->not->toContain('template.innerHTML')
        ->and($select2)->not->toContain('dropdownParent')
        ->and($accordion)->not->toContain('overflow-hidden');
});

it('renders ipd lab and imaging sections when accordion config is off', function () {
    config([
        'visits.dual_write_enabled' => false,
        'visits.read_from_child.ipd' => true,
        'visits.workflow_accordion_ui' => false,
    ]);

    $visit = makeAdmittedIpdVisit('NOACC');

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertSee('data-workflow-section="lab"', false)
        ->assertSee('data-workflow-section="imaging"', false);
});

it('workflow tab script does not return early before highlighting the selected tab', function () {
    $scripts = file_get_contents(resource_path('views/admin/visits/workflow/_shared/_scripts.blade.php'));
    $accordion = file_get_contents(resource_path('js/workflow-accordion.js'));
    $ipdJs = file_get_contents(resource_path('js/visit-workflow-ipd.js'));

    expect($scripts)
        ->toContain('switchVisitWorkflowTab')
        ->not->toContain('openWorkflowAccordionSection(tabName))')
        ->and($ipdJs)->toContain('window.switchVisitWorkflowTab')
        ->and($ipdJs)->toContain('aria-current')
        ->and($accordion)->toContain('if (sectionId)')
        ->and($accordion)->toContain('return false');
});

it('ipd handler exposes workflow permission flags', function () {
    config(['visits.dual_write_enabled' => false]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'ipd',
        'visit_datetime' => now(),
        'status' => 'registered',
        'doctor_id' => null,
    ]);

    IpdVisit::create(['visit_id' => $visit->id]);

    $handler = new IpdVisitHandler;
    $data = $handler->workflowData($visit);

    expect($data['show_ipd_ui'])->toBeTrue()
        ->and($data['append_only_vitals'])->toBeTrue()
        ->and($handler->canConsult($visit))->toBeFalse()
        ->and($handler->resolveInitialTab($visit))->toBe('admission');
});
