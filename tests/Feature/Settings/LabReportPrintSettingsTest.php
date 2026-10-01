<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\LabReportRosterDoctor;
use App\Models\ModuleRegistry;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Support\LabReportAccentContrast;
use App\Support\LabReportPrintSettings;
use App\Support\SettingsSectionRegistry;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
    $this->withoutVite();

    Cache::flush();
});

function labReportPrintUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Lab Report Print User',
        'email' => 'lab-report-print-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    if ($permissions !== []) {
        $user->givePermissionTo($permissions);
    }

    return $user;
}

function bindLabReportPrintTenant(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

it('registers settings.lab-report-print in module and section catalogs', function () {
    expect(ModuleRegistry::all())->toContain('settings.lab-report-print')
        ->and(ModuleRegistry::parentOf('settings.lab-report-print'))->toBe('settings')
        ->and(ModuleRegistry::moduleForRoute('settings.lab-report-print.edit'))
        ->toBe('settings.lab-report-print')
        ->and(ModuleRegistry::moduleForRoute('settings.lab-report-print.update'))
        ->toBe('settings.lab-report-print')
        ->and(SettingsSectionRegistry::permissionName('settings.lab-report-print'))
        ->toBe('access settings.lab-report-print')
        ->and(collect(SettingsSectionRegistry::children())->pluck('key')->all())
        ->toContain('settings.lab-report-print');
});

it('defaults header toggles to true and footer contact toggles to false', function () {
    expect(LabReportPrintSettings::get())->toBe([
        'show_logo' => true,
        'show_qr' => true,
        'show_hospital_address' => true,
        'show_hospital_phone' => true,
        'show_hospital_email' => true,
        'show_hospital_website' => true,
        'show_phc_registration' => true,
        'show_footer_address' => false,
        'show_footer_phone' => false,
        'show_footer_email' => false,
        'show_footer_website' => false,
        'show_patient_band' => true,
        'show_reviewers' => true,
        'show_page_numbers' => true,
        'previous_values_count' => 3,
        'accent_color' => '#0F766E',
    ]);
});

it('defaults accent_color to #0F766E for new tenants', function () {
    expect(LabReportPrintSettings::get()['accent_color'])->toBe('#0F766E');
});

it('defaults accent_color when saved settings omit the key', function () {
    Setting::set('lab_report_print', json_encode([
        'show_logo' => true,
        'show_qr' => true,
        'show_hospital_address' => true,
        'show_hospital_phone' => true,
        'show_hospital_email' => true,
        'show_hospital_website' => true,
        'show_patient_band' => true,
        'show_reviewers' => true,
        'show_page_numbers' => true,
        'previous_values_count' => 3,
    ]));

    expect(LabReportPrintSettings::get()['accent_color'])->toBe('#0F766E');
});

it('defaults previous_values_count to 3 for new tenants', function () {
    expect(LabReportPrintSettings::get())->toMatchArray([
        'previous_values_count' => 3,
        'show_logo' => true,
    ]);
});

it('defaults footer contact toggles to false for existing saved settings missing those keys', function () {
    Setting::set('lab_report_print', json_encode([
        'show_logo' => true,
        'show_qr' => true,
        'show_hospital_address' => true,
        'show_hospital_phone' => true,
        'show_hospital_email' => true,
        'show_hospital_website' => true,
        'show_patient_band' => true,
        'show_reviewers' => true,
        'show_page_numbers' => true,
        'previous_values_count' => 3,
    ]));

    expect(LabReportPrintSettings::get())->toMatchArray([
        'show_hospital_phone' => true,
        'show_footer_phone' => false,
        'show_footer_email' => false,
        'show_footer_address' => false,
        'show_footer_website' => false,
    ]);
});

it('defaults show_phc_registration on, including for tenants whose saved JSON predates it', function () {
    expect(LabReportPrintSettings::get()['show_phc_registration'])->toBeTrue();

    Setting::set('lab_report_print', json_encode([
        'show_logo' => true,
        'show_qr' => true,
        'show_hospital_address' => true,
        'show_hospital_phone' => true,
        'show_hospital_email' => true,
        'show_hospital_website' => true,
        'show_patient_band' => true,
        'show_reviewers' => true,
        'show_page_numbers' => true,
        'previous_values_count' => 3,
        'accent_color' => '#0F766E',
    ]));

    expect(LabReportPrintSettings::get()['show_phc_registration'])->toBeTrue();
});

it('persists show_phc_registration off from the settings form', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    $this->put(route('settings.lab-report-print.update'), [
        'show_logo' => '1',
        'show_qr' => '1',
        'show_hospital_address' => '1',
        'show_hospital_phone' => '1',
        'show_hospital_email' => '1',
        'show_hospital_website' => '1',
        'show_phc_registration' => '0',
        'show_footer_address' => '0',
        'show_footer_phone' => '0',
        'show_footer_email' => '0',
        'show_footer_website' => '0',
        'show_patient_band' => '1',
        'show_reviewers' => '1',
        'show_page_numbers' => '1',
        'previous_values_count' => '3',
        'accent_color' => '#0F766E',
    ])->assertRedirect(route('settings.lab-report-print.edit'))
        ->assertSessionHas('success');

    expect(LabReportPrintSettings::get()['show_phc_registration'])->toBeFalse();
});

it('lists the PHC toggle among header toggles on the settings page', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    $this->get(route('settings.lab-report-print.edit'))
        ->assertOk()
        ->assertSee('name="show_phc_registration"', false)
        ->assertSee('Show PHC registration number (header)');
});

it('forbids lab report print settings without the child module', function () {
    bindLabReportPrintTenant(['settings']);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    $this->get(route('settings.lab-report-print.edit'))
        ->assertForbidden();
});

it('forbids lab report print settings without the access permission', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.hospital-info']));

    $this->get(route('settings.lab-report-print.edit'))
        ->assertForbidden();
});

it('renders and saves lab report print toggles', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    $this->get(route('settings.lab-report-print.edit'))
        ->assertOk()
        ->assertSee('Lab Report Print')
        ->assertSee('name="show_logo"', false)
        ->assertSee('name="show_qr"', false)
        ->assertSee('name="show_page_numbers"', false)
        ->assertSee('name="show_footer_phone"', false)
        ->assertSee('Footer contact line', false)
        ->assertSee('(header)', false);

    $this->put(route('settings.lab-report-print.update'), [
        'show_logo' => '1',
        'show_qr' => '0',
        'show_hospital_address' => '1',
        'show_hospital_phone' => '0',
        'show_hospital_email' => '1',
        'show_hospital_website' => '0',
        'show_footer_address' => '0',
        'show_footer_phone' => '1',
        'show_footer_email' => '0',
        'show_footer_website' => '1',
        'show_patient_band' => '1',
        'show_reviewers' => '0',
        'show_page_numbers' => '1',
        'previous_values_count' => '3',
        'accent_color' => '#0F766E',
    ])->assertRedirect(route('settings.lab-report-print.edit'))
        ->assertSessionHas('success');

    expect(LabReportPrintSettings::get())->toMatchArray([
        'show_logo' => true,
        'show_qr' => false,
        'show_hospital_address' => true,
        'show_hospital_phone' => false,
        'show_hospital_email' => true,
        'show_hospital_website' => false,
        'show_footer_address' => false,
        'show_footer_phone' => true,
        'show_footer_email' => false,
        'show_footer_website' => true,
        'show_patient_band' => true,
        'show_reviewers' => false,
        'show_page_numbers' => true,
        'previous_values_count' => 3,
        'accent_color' => '#0F766E',
    ])->and(Setting::get('lab_report_print'))->not->toBeNull();
});

it('normalizes and persists accent_color from settings update', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    $this->put(route('settings.lab-report-print.update'), [
        'show_logo' => '1',
        'show_qr' => '1',
        'show_hospital_address' => '1',
        'show_hospital_phone' => '1',
        'show_hospital_email' => '1',
        'show_hospital_website' => '1',
        'show_footer_address' => '0',
        'show_footer_phone' => '0',
        'show_footer_email' => '0',
        'show_footer_website' => '0',
        'show_patient_band' => '1',
        'show_reviewers' => '1',
        'show_page_numbers' => '1',
        'previous_values_count' => '3',
        'accent_color' => '#1a2b3c',
    ])->assertRedirect();

    expect(LabReportPrintSettings::get()['accent_color'])->toBe('#1A2B3C');
});

it('rejects invalid accent_color values', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    foreach (['red', '#FFF', '#fffffff', '123456', ''] as $bad) {
        $this->from(route('settings.lab-report-print.edit'))
            ->put(route('settings.lab-report-print.update'), [
                'show_logo' => '1',
                'show_qr' => '1',
                'show_hospital_address' => '1',
                'show_hospital_phone' => '1',
                'show_hospital_email' => '1',
                'show_hospital_website' => '1',
                'show_patient_band' => '1',
                'show_reviewers' => '1',
                'show_page_numbers' => '1',
                'previous_values_count' => '3',
                'accent_color' => $bad,
            ])
            ->assertSessionHasErrors('accent_color');
    }
});

it('falls back to default when stored accent_color is corrupt', function () {
    Setting::set('lab_report_print', json_encode([
        'accent_color' => 'not-a-color',
        'previous_values_count' => 3,
    ]));

    expect(LabReportPrintSettings::get()['accent_color'])->toBe('#0F766E');
});

it('shows accent color controls on the lab report print settings screen', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    $this->get(route('settings.lab-report-print.edit'))
        ->assertOk()
        ->assertSee('Report accent color', false)
        ->assertSee('name="accent_color"', false)
        ->assertSee('type="color"', false)
        ->assertSee('id="lab-report-accent-hex"', false)
        ->assertSee('id="lab-report-accent-contrast"', false);
});

it('still saves when accent_color contrast is below 4.5:1', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    $this->put(route('settings.lab-report-print.update'), [
        'show_logo' => '1',
        'show_qr' => '1',
        'show_hospital_address' => '1',
        'show_hospital_phone' => '1',
        'show_hospital_email' => '1',
        'show_hospital_website' => '1',
        'show_footer_address' => '0',
        'show_footer_phone' => '0',
        'show_footer_email' => '0',
        'show_footer_website' => '0',
        'show_patient_band' => '1',
        'show_reviewers' => '1',
        'show_page_numbers' => '1',
        'previous_values_count' => '3',
        'accent_color' => '#FDE68A',
    ])->assertRedirect(route('settings.lab-report-print.edit'))
        ->assertSessionHas('success')
        ->assertSessionMissing('errors');

    expect(LabReportPrintSettings::get()['accent_color'])->toBe('#FDE68A')
        ->and(LabReportAccentContrast::failsMinimum('#FDE68A'))->toBeTrue();
});

it('shows the amber warn banner for a saved accent between 3:1 and 4.5:1', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    Setting::set('lab_report_print', json_encode([
        'accent_color' => '#33847E',
        'previous_values_count' => 3,
    ]));

    $this->get(route('settings.lab-report-print.edit'))
        ->assertOk()
        ->assertSee('rounded-lg border px-3 py-2 text-xs border-amber-300 bg-amber-50 text-amber-900', false)
        ->assertSee('Contrast vs white: 4.4:1 — below 4.5:1, so white header, footer and section-bar text may be hard to read, especially in black & white. Consider a darker color. You can still save.')
        ->assertDontSee('OK for white header/footer text');
});

it('shows the OK banner copy for an accent that meets 4.5:1', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    Setting::set('lab_report_print', json_encode([
        'accent_color' => '#0F766E',
        'previous_values_count' => 3,
    ]));

    $this->get(route('settings.lab-report-print.edit'))
        ->assertOk()
        ->assertSee('rounded-lg border px-3 py-2 text-xs border-emerald-300 bg-emerald-50 text-emerald-900', false)
        ->assertSee('Contrast vs white: 5.5:1 — OK for white header/footer text (needs 4.5:1).')
        ->assertDontSee('You can still save.');
});

it('still saves an accent between 3:1 and 4.5:1 (warn-only)', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    $this->put(route('settings.lab-report-print.update'), [
        'show_logo' => '1',
        'show_qr' => '1',
        'show_hospital_address' => '1',
        'show_hospital_phone' => '1',
        'show_hospital_email' => '1',
        'show_hospital_website' => '1',
        'show_footer_address' => '0',
        'show_footer_phone' => '0',
        'show_footer_email' => '0',
        'show_footer_website' => '0',
        'show_patient_band' => '1',
        'show_reviewers' => '1',
        'show_page_numbers' => '1',
        'previous_values_count' => '3',
        'accent_color' => '#33847E',
    ])->assertRedirect(route('settings.lab-report-print.edit'))
        ->assertSessionHas('success')
        ->assertSessionMissing('errors');

    expect(LabReportPrintSettings::get()['accent_color'])->toBe('#33847E')
        ->and(LabReportAccentContrast::failsMinimum('#33847E'))->toBeTrue();
});

it('saves previous_values_count from radio selection', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    $this->get(route('settings.lab-report-print.edit'))
        ->assertOk()
        ->assertSee('name="previous_values_count"', false)
        ->assertSee('Previous results per parameter', false);

    $this->put(route('settings.lab-report-print.update'), [
        'show_logo' => '1',
        'show_qr' => '1',
        'show_hospital_address' => '1',
        'show_hospital_phone' => '1',
        'show_hospital_email' => '1',
        'show_hospital_website' => '1',
        'show_patient_band' => '1',
        'show_reviewers' => '1',
        'show_page_numbers' => '1',
        'previous_values_count' => '5',
        'accent_color' => '#0F766E',
    ])->assertRedirect();

    expect(LabReportPrintSettings::get()['previous_values_count'])->toBe(5);
});

it('rejects previous_values_count outside 1-5', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    $this->from(route('settings.lab-report-print.edit'))
        ->put(route('settings.lab-report-print.update'), [
            'show_logo' => '1',
            'show_qr' => '1',
            'show_hospital_address' => '1',
            'show_hospital_phone' => '1',
            'show_hospital_email' => '1',
            'show_hospital_website' => '1',
            'show_patient_band' => '1',
            'show_reviewers' => '1',
            'show_page_numbers' => '1',
            'previous_values_count' => '9',
            'accent_color' => '#0F766E',
        ])
        ->assertSessionHasErrors('previous_values_count');
});

it('scopes roster doctor_ids exists rule to tenant.doctors', function () {
    $existsRule = collect((new \App\Http\Requests\UpdateLabReportRosterRequest)->rules()['doctor_ids.*'])
        ->first(fn ($rule) => $rule instanceof \Illuminate\Validation\Rules\Exists);

    expect($existsRule)->not->toBeNull()
        ->and((string) $existsRule)->toBe('exists:tenant.doctors,id');
});

it('accepts a real tenant doctor id when saving the lab report consultant roster', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    $department = Department::create([
        'name' => 'Roster Tenant Dept',
        'code' => 'RTD'.uniqid(),
        'status' => 'active',
    ]);

    $doctor = Doctor::create([
        'name' => 'Dr Tenant Roster',
        'specialization' => 'Pathology',
        'qualification' => 'MBBS',
        'phone' => '03001119999',
        'email' => 'roster-tenant-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $this->put(route('settings.lab-report-print.roster'), [
        'doctor_ids' => [$doctor->id],
    ])->assertRedirect(route('settings.lab-report-print.edit'))
        ->assertSessionHas('success')
        ->assertSessionDoesntHaveErrors();

    expect(LabReportRosterDoctor::query()->pluck('doctor_id')->all())->toBe([$doctor->id]);
});

it('persists ordered consultant roster from settings', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    $department = Department::create([
        'name' => 'Roster Dept',
        'code' => 'RST'.uniqid(),
        'status' => 'active',
    ]);

    $doctorA = Doctor::create([
        'name' => 'Dr Roster A',
        'specialization' => 'Pathology',
        'qualification' => 'MBBS',
        'phone' => '03001110001',
        'email' => 'roster-a-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $doctorB = Doctor::create([
        'name' => 'Dr Roster B',
        'specialization' => 'Hematology',
        'qualification' => 'FCPS',
        'phone' => '03001110002',
        'email' => 'roster-b-'.uniqid().'@example.com',
        'gender' => 'female',
        'experience_years' => 7,
        'consultation_fee' => 1200,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $this->get(route('settings.lab-report-print.edit'))
        ->assertOk()
        ->assertSee('Consultant roster')
        ->assertSee('id="lab-report-roster-form"', false);

    $this->put(route('settings.lab-report-print.roster'), [
        'doctor_ids' => [$doctorB->id, $doctorA->id],
    ])->assertRedirect(route('settings.lab-report-print.edit'))
        ->assertSessionHas('success');

    expect(
        LabReportRosterDoctor::query()->orderBy('sort_order')->pluck('doctor_id')->all()
    )->toBe([$doctorB->id, $doctorA->id]);

    $this->put(route('settings.lab-report-print.roster'), [
        'doctor_ids' => [$doctorA->id],
    ])->assertRedirect(route('settings.lab-report-print.edit'));

    expect(
        LabReportRosterDoctor::query()->orderBy('sort_order')->pluck('doctor_id')->all()
    )->toBe([$doctorA->id]);
});

it('rejects saving an empty consultant roster', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    $department = Department::create([
        'name' => 'Roster Empty Dept',
        'code' => 'RED'.uniqid(),
        'status' => 'active',
    ]);

    $doctor = Doctor::create([
        'name' => 'Dr Keep Roster',
        'specialization' => 'Pathology',
        'qualification' => 'MBBS',
        'phone' => '03001110009',
        'email' => 'roster-keep-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    LabReportRosterDoctor::create(['doctor_id' => $doctor->id, 'sort_order' => 0]);

    $this->from(route('settings.lab-report-print.edit'))
        ->put(route('settings.lab-report-print.roster'), [
            'doctor_ids' => [],
        ])
        ->assertRedirect(route('settings.lab-report-print.edit'))
        ->assertSessionHasErrors(['doctor_ids' => 'Add at least one consultant to the roster.']);

    expect(LabReportRosterDoctor::query()->count())->toBe(1);
});
