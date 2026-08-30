<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\PrescriptionPrintTemplate;
use App\Models\User;
use App\Services\PrescriptionPrintLayoutBuilder;
use App\Services\PrescriptionPrintTemplateService;
use App\Support\PrescriptionPrintFieldCatalog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);
});

function printTemplateUser(array $permissions = []): User
{
    $user = User::create([
        'name' => 'Print Template User',
        'email' => 'print-template-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

function printTemplatePayload(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => 'Main prescription',
        'mode' => 'overlay_physical',
        'paper_size' => 'A4',
        'orientation' => 'portrait',
        'doctor_id' => null,
        'rx_start_y' => 80,
        'rx_row_height' => 8,
        'rx_max_rows' => 12,
        'rx_overflow_policy' => 'second_page_plain',
        'fields' => collect(PrescriptionPrintFieldCatalog::keys())
            ->mapWithKeys(fn (string $key, int $index) => [$key => [
                'x_mm' => 15,
                'y_mm' => 20 + ($index * 6),
                'font_size' => 10,
                'font_weight' => 'normal',
                'align' => 'left',
                'visible' => in_array($key, PrescriptionPrintFieldCatalog::coreKeys(), true) ? '1' : '0',
            ]])
            ->all(),
    ], $overrides);
}

function createPrintTemplate(array $overrides = [], bool $seedFields = false): PrescriptionPrintTemplate
{
    $template = PrescriptionPrintTemplate::create(array_merge([
        'name' => 'Existing template',
        'mode' => 'overlay_physical',
        'paper_size' => 'A4',
        'orientation' => 'portrait',
        'is_active' => false,
        'rx_start_y' => 80,
        'rx_row_height' => 8,
        'rx_max_rows' => 12,
        'rx_overflow_policy' => 'second_page_plain',
    ], $overrides));

    if ($seedFields) {
        (new PrescriptionPrintTemplateService)->seedDefaultFields($template);
    }

    return $template;
}

function printTemplateDoctor(): Doctor
{
    $department = Department::create([
        'name' => 'Print Templates',
        'code' => 'PRINT-TEMPLATES',
        'status' => 'active',
    ]);

    return Doctor::create([
        'name' => 'Print Template Doctor',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03001112233',
        'email' => 'print-template-doctor-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);
}

it('requires authentication and prescription print settings access', function () {
    $this->get(route('settings.prescription-print-templates.index'))
        ->assertRedirect(route('login'));

    $this->actingAs(printTemplateUser());
    $this->get(route('settings.prescription-print-templates.index'))->assertForbidden();
});

it('allows child parent and legacy settings permissions but rejects hospital-only access', function () {
    $this->actingAs(printTemplateUser(['access settings.prescription-print']));
    $this->get(route('settings.prescription-print-templates.index'))->assertOk();

    $this->actingAs(printTemplateUser(['access settings.hospital-info']));
    $this->get(route('settings.prescription-print-templates.index'))->assertForbidden();

    $this->actingAs(printTemplateUser(['access settings']));
    $this->get(route('settings.prescription-print-templates.index'))->assertOk();

    $this->actingAs(printTemplateUser(['manage settings']));
    $this->get(route('settings.prescription-print-templates.index'))->assertOk();
});

it('renders the index inside the settings shell for parent access', function () {
    $this->actingAs(printTemplateUser(['access settings']));

    $this->get(route('settings.prescription-print-templates.index'))
        ->assertOk()
        ->assertSee('Hospital Info')
        ->assertSee('Prescription Print Templates');
});

it('loads the visual editor script on the edit view', function () {
    $this->actingAs(printTemplateUser(['access settings.prescription-print']));
    $template = createPrintTemplate([], true);

    $this->get(route('settings.prescription-print-templates.edit', $template))
        ->assertOk()
        ->assertSee('prescription-print-template-editor', false);
});

it('stores a template and one field for every catalog key', function () {
    $this->actingAs(printTemplateUser(['access settings.prescription-print']));

    $response = $this->post(route('settings.prescription-print-templates.store'), printTemplatePayload());

    $template = PrescriptionPrintTemplate::where('name', 'Main prescription')->firstOrFail();

    $response->assertRedirect(route('settings.prescription-print-templates.edit', $template));

    expect($template->fields)->toHaveCount(count(PrescriptionPrintFieldCatalog::keys()))
        ->and($template->fields->pluck('field_key')->sort()->values()->all())
        ->toBe(collect(PrescriptionPrintFieldCatalog::keys())->sort()->values()->all());
});

it('refuses to activate an incomplete template and leaves it inactive', function () {
    $this->actingAs(printTemplateUser(['access settings.prescription-print']));
    $template = createPrintTemplate();

    $this->post(route('settings.prescription-print-templates.activate', $template))
        ->assertStatus(422)
        ->assertSessionHasErrors('is_active');

    expect($template->fresh()->is_active)->toBeFalse();
});

it('activates a complete clinic default and deactivates the previous default', function () {
    $this->actingAs(printTemplateUser(['access settings.prescription-print']));
    $previous = createPrintTemplate(['name' => 'Previous', 'is_active' => true], true);
    $replacement = createPrintTemplate(['name' => 'Replacement'], true);

    $this->post(route('settings.prescription-print-templates.activate', $replacement))
        ->assertRedirect(route('settings.prescription-print-templates.index'));

    expect($previous->fresh()->is_active)->toBeFalse()
        ->and($replacement->fresh()->is_active)->toBeTrue();
});

it('deactivates the clinic default when an active doctor template becomes the default', function () {
    $this->actingAs(printTemplateUser(['access settings.prescription-print']));
    $doctor = printTemplateDoctor();
    $clinicDefault = createPrintTemplate([
        'name' => 'Clinic default',
        'is_active' => true,
    ], true);
    $doctorTemplate = createPrintTemplate([
        'name' => 'Doctor template',
        'doctor_id' => $doctor->id,
        'is_active' => true,
    ], true);

    $this->put(
        route('settings.prescription-print-templates.update', $doctorTemplate),
        printTemplatePayload([
            'name' => 'Doctor template',
            'doctor_id' => null,
        ])
    )->assertRedirect(route('settings.prescription-print-templates.index'));

    expect($clinicDefault->fresh()->is_active)->toBeFalse()
        ->and($doctorTemplate->fresh()->doctor_id)->toBeNull()
        ->and($doctorTemplate->fresh()->is_active)->toBeTrue()
        ->and(PrescriptionPrintTemplate::query()
            ->whereNull('doctor_id')
            ->where('is_active', true)
            ->count())->toBe(1);
});

it('requires a background image when storing a digitized template', function () {
    $this->actingAs(printTemplateUser(['access settings.prescription-print']));

    $this->post(
        route('settings.prescription-print-templates.store'),
        printTemplatePayload(['mode' => 'digitized_background'])
    )
        ->assertStatus(422)
        ->assertSessionHasErrors('background_image');

    expect(PrescriptionPrintTemplate::count())->toBe(0);
});

it('stores an optional overlay image without using it as a printed background', function () {
    Storage::fake('public');
    $this->actingAs(printTemplateUser(['access settings.prescription-print']));
    $payload = printTemplatePayload();
    $payload['background_image'] = UploadedFile::fake()->image('letterhead.png');

    $response = $this->post(route('settings.prescription-print-templates.store'), $payload);

    $template = PrescriptionPrintTemplate::firstOrFail();
    $response->assertRedirect(route('settings.prescription-print-templates.edit', $template));
    Storage::disk('public')->assertExists($template->background_image_path);

    $layout = (new PrescriptionPrintLayoutBuilder)->buildCalibration($template);
    expect($template->background_image_path)->not->toBeNull()
        ->and($layout['background_path'])->toBeNull()
        ->and($layout['pages'][0]['show_background'])->toBeFalse();
});

it('returns calibration pdf only for overlay templates', function () {
    $this->actingAs(printTemplateUser(['access settings.prescription-print']));
    $overlay = createPrintTemplate([], true);
    $digitized = createPrintTemplate(['name' => 'Digitized', 'mode' => 'digitized_background'], true);

    $response = $this->get(route('settings.prescription-print-templates.calibration', $overlay));
    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');

    $this->from(route('settings.prescription-print-templates.index'))
        ->get(route('settings.prescription-print-templates.calibration', $digitized))
        ->assertRedirect(route('settings.prescription-print-templates.index'))
        ->assertSessionHasErrors('mode');
});

it('updates field settings deactivates and deletes templates', function () {
    $this->actingAs(printTemplateUser(['access settings.prescription-print']));
    $template = createPrintTemplate(['is_active' => true], true);
    $payload = printTemplatePayload([
        'name' => 'Updated template',
        'fields' => ['patient_name' => ['x_mm' => 42]],
    ]);

    $this->put(route('settings.prescription-print-templates.update', $template), $payload)
        ->assertRedirect(route('settings.prescription-print-templates.index'));

    expect($template->fresh()->name)->toBe('Updated template')
        ->and($template->fields()->where('field_key', 'patient_name')->value('x_mm'))->toEqual(42.0);

    $this->post(route('settings.prescription-print-templates.deactivate', $template))
        ->assertRedirect(route('settings.prescription-print-templates.index'));
    expect($template->fresh()->is_active)->toBeFalse();

    $this->delete(route('settings.prescription-print-templates.destroy', $template))
        ->assertRedirect(route('settings.prescription-print-templates.index'));
    expect(PrescriptionPrintTemplate::find($template->id))->toBeNull();
});
