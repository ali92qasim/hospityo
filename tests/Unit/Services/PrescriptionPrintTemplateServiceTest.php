<?php

use App\Models\PrescriptionPrintField;
use App\Models\PrescriptionPrintTemplate;
use App\Services\PrescriptionPrintTemplateService;
use App\Support\PrescriptionPrintFieldCatalog;
use Illuminate\Validation\ValidationException;

function seedCoreFields(PrescriptionPrintTemplate $template, bool $visible = true): void
{
    foreach (PrescriptionPrintFieldCatalog::coreKeys() as $index => $key) {
        PrescriptionPrintField::create([
            'template_id' => $template->id,
            'field_key' => $key,
            'x_mm' => 10,
            'y_mm' => 10 + ($index * 8),
            'font_size' => 10,
            'font_weight' => 'normal',
            'align' => 'left',
            'visible' => $visible,
        ]);
    }
}

it('does not treat a template as complete until core fields are visible and positioned', function () {
    $template = PrescriptionPrintTemplate::create([
        'name' => 'Incomplete',
        'mode' => 'overlay_physical',
        'paper_size' => 'A4',
        'orientation' => 'portrait',
        'is_active' => false,
        'rx_start_y' => 80,
        'rx_row_height' => 8,
        'rx_max_rows' => 10,
        'rx_overflow_policy' => 'cap_with_note',
    ]);

    $service = new PrescriptionPrintTemplateService;

    expect($service->isComplete($template))->toBeFalse();

    seedCoreFields($template, visible: false);
    expect($service->isComplete($template->fresh()))->toBeFalse();

    $template->fields()->update(['visible' => true]);
    expect($service->isComplete($template->fresh()))->toBeTrue();
});

it('refuses to activate an incomplete template', function () {
    $template = PrescriptionPrintTemplate::create([
        'name' => 'Incomplete',
        'mode' => 'overlay_physical',
        'paper_size' => 'A4',
        'orientation' => 'portrait',
        'is_active' => false,
        'rx_start_y' => 80,
        'rx_row_height' => 8,
        'rx_max_rows' => 10,
        'rx_overflow_policy' => 'cap_with_note',
    ]);

    expect(fn () => (new PrescriptionPrintTemplateService)->activate($template))
        ->toThrow(ValidationException::class);

    expect($template->fresh()->is_active)->toBeFalse();
    expect(PrescriptionPrintTemplate::resolvePrintTemplate(null))->toBeNull();
});

it('deactivates the previous active template for the same doctor_id when activating another', function () {
    $first = PrescriptionPrintTemplate::create([
        'name' => 'First',
        'mode' => 'overlay_physical',
        'paper_size' => 'A4',
        'orientation' => 'portrait',
        'is_active' => false,
        'rx_start_y' => 80,
        'rx_row_height' => 8,
        'rx_max_rows' => 10,
        'rx_overflow_policy' => 'cap_with_note',
    ]);
    $second = PrescriptionPrintTemplate::create([
        'name' => 'Second',
        'mode' => 'overlay_physical',
        'paper_size' => 'A4',
        'orientation' => 'portrait',
        'is_active' => false,
        'rx_start_y' => 80,
        'rx_row_height' => 8,
        'rx_max_rows' => 10,
        'rx_overflow_policy' => 'cap_with_note',
    ]);
    seedCoreFields($first);
    seedCoreFields($second);

    $service = new PrescriptionPrintTemplateService;
    $service->activate($first);
    $service->activate($second);

    expect($first->fresh()->is_active)->toBeFalse()
        ->and($second->fresh()->is_active)->toBeTrue()
        ->and(PrescriptionPrintTemplate::resolvePrintTemplate(null)?->id)->toBe($second->id);
});

it('requires a background image to activate digitized_background mode', function () {
    $template = PrescriptionPrintTemplate::create([
        'name' => 'Scan',
        'mode' => 'digitized_background',
        'paper_size' => 'A4',
        'orientation' => 'portrait',
        'background_image_path' => null,
        'is_active' => false,
        'rx_start_y' => 80,
        'rx_row_height' => 8,
        'rx_max_rows' => 10,
        'rx_overflow_policy' => 'cap_with_note',
    ]);
    seedCoreFields($template);

    expect(fn () => (new PrescriptionPrintTemplateService)->activate($template))
        ->toThrow(ValidationException::class);
});
