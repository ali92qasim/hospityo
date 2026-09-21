<?php

use App\Models\ModuleRegistry;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Support\LabReportPrintSettings;
use App\Support\SettingsSectionRegistry;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);

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

it('defaults all lab report print toggles to true', function () {
    expect(LabReportPrintSettings::get())->toBe([
        'show_logo' => true,
        'show_qr' => true,
        'show_hospital_address' => true,
        'show_hospital_phone' => true,
        'show_hospital_email' => true,
        'show_hospital_website' => true,
        'show_patient_band' => true,
        'show_reviewers' => true,
        'show_page_numbers' => true,
    ]);
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
        ->assertSee('name="show_page_numbers"', false);

    $this->put(route('settings.lab-report-print.update'), [
        'show_logo' => '1',
        'show_qr' => '0',
        'show_hospital_address' => '1',
        'show_hospital_phone' => '0',
        'show_hospital_email' => '1',
        'show_hospital_website' => '0',
        'show_patient_band' => '1',
        'show_reviewers' => '0',
        'show_page_numbers' => '1',
    ])->assertRedirect(route('settings.lab-report-print.edit'))
        ->assertSessionHas('success');

    expect(LabReportPrintSettings::get())->toMatchArray([
        'show_logo' => true,
        'show_qr' => false,
        'show_hospital_address' => true,
        'show_hospital_phone' => false,
        'show_hospital_email' => true,
        'show_hospital_website' => false,
        'show_patient_band' => true,
        'show_reviewers' => false,
        'show_page_numbers' => true,
    ])->and(Setting::get('lab_report_print'))->not->toBeNull();
});
