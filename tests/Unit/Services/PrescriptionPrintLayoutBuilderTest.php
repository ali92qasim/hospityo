<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\PrescriptionPrintField;
use App\Models\PrescriptionPrintTemplate;
use App\Models\Visit;
use App\Services\PrescriptionPrintLayoutBuilder;
use Illuminate\Support\Facades\Storage;

function layoutVisitWithThreeMeds(): Visit
{
    $patient = Patient::create([
        'name' => 'Ali Khan',
        'gender' => 'male',
        'age' => 42,
        'phone' => '03001112233',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03004445566',
        'emergency_relation' => 'Brother',
    ]);
    $department = Department::create(['name' => 'OPD', 'code' => 'OPD-LAY', 'status' => 'active']);
    $doctor = Doctor::create([
        'name' => 'Sara Ahmed',
        'specialization' => 'Medicine',
        'qualification' => 'MBBS',
        'phone' => '03006667788',
        'email' => 'layout-'.uniqid().'@example.com',
        'gender' => 'female',
        'experience_years' => 8,
        'consultation_fee' => 1500,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);
    $visit = Visit::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now(),
    ]);
    $prescription = Prescription::create([
        'visit_id' => $visit->id,
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'prescribed_date' => now(),
        'status' => 'pending',
    ]);
    foreach (['Amoxicillin', 'Ibuprofen', 'Cetirizine'] as $name) {
        $medicine = Medicine::create([
            'name' => $name,
            'generic_name' => $name,
            'status' => 'active',
            'manage_stock' => false,
        ]);
        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $medicine->id,
            'quantity' => 1,
            'unit_price' => 0,
            'total_price' => 0,
        ]);
    }

    return $visit->fresh(['prescriptions.items.medicine', 'patient', 'doctor']);
}

function overlayTemplate(string $policy): PrescriptionPrintTemplate
{
    $template = PrescriptionPrintTemplate::create([
        'name' => 'Overlay',
        'mode' => 'overlay_physical',
        'paper_size' => 'A4',
        'orientation' => 'portrait',
        'background_image_path' => 'tenants/demo/ignored-guide.png',
        'is_active' => false,
        'rx_start_y' => 80,
        'rx_row_height' => 10,
        'rx_max_rows' => 2,
        'rx_overflow_policy' => $policy,
    ]);
    PrescriptionPrintField::create([
        'template_id' => $template->id,
        'field_key' => 'patient_name',
        'x_mm' => 25,
        'y_mm' => 40,
        'font_size' => 12,
        'font_weight' => 'bold',
        'align' => 'left',
        'visible' => true,
    ]);

    return $template->fresh('fields');
}

it('places resolved field values at stored millimeter coordinates', function () {
    $layout = (new PrescriptionPrintLayoutBuilder)->build(layoutVisitWithThreeMeds(), overlayTemplate('cap_with_note'));
    $patient = collect($layout['pages'][0]['elements'])->first(fn ($el) => str_contains($el['text'], 'Ali Khan'));

    expect($patient)->not->toBeNull()
        ->and($patient['x_mm'])->toBe(25.0)
        ->and($patient['y_mm'])->toBe(40.0)
        ->and($layout['background_path'])->toBeNull();
});

it('continues overflowing rx rows on a plain second page', function () {
    $layout = (new PrescriptionPrintLayoutBuilder)->build(layoutVisitWithThreeMeds(), overlayTemplate('second_page_plain'));

    expect($layout['pages'])->toHaveCount(2)
        ->and($layout['pages'][1]['show_background'])->toBeFalse();

    $page1Text = collect($layout['pages'][0]['elements'])->pluck('text')->implode(' ');
    $page2Text = collect($layout['pages'][1]['elements'])->pluck('text')->implode(' ');
    expect($page1Text)->toContain('Amoxicillin')->toContain('Ibuprofen')
        ->and($page2Text)->toContain('Cetirizine');
});

it('shrinks rx spacing to fit every row on one page', function () {
    $layout = (new PrescriptionPrintLayoutBuilder)->build(layoutVisitWithThreeMeds(), overlayTemplate('shrink_font'));
    $rx = collect($layout['pages'][0]['elements'])->filter(fn ($el) => str_contains($el['text'], '. '))->values();

    expect($layout['pages'])->toHaveCount(1)
        ->and($rx)->toHaveCount(3)
        ->and($rx[1]['y_mm'] - $rx[0]['y_mm'])->toEqual(20 / 3);
});

it('caps overflowing rx rows with a continuation note', function () {
    $layout = (new PrescriptionPrintLayoutBuilder)->build(layoutVisitWithThreeMeds(), overlayTemplate('cap_with_note'));
    $texts = collect($layout['pages'][0]['elements'])->pluck('text');

    expect($layout['pages'])->toHaveCount(1)
        ->and($texts->contains('continued — see attached'))->toBeTrue()
        ->and($texts->contains(fn ($text) => str_contains($text, 'Cetirizine')))->toBeFalse();
});

it('uses the stored image as page-one background only in digitized mode', function () {
    Storage::fake('public');
    $path = 'tenants/demo/letterhead.png';
    Storage::disk('public')->put($path, 'fake-image');

    $template = overlayTemplate('second_page_plain');
    $template->update(['mode' => 'digitized_background', 'background_image_path' => $path]);
    $layout = (new PrescriptionPrintLayoutBuilder)->build(layoutVisitWithThreeMeds(), $template->fresh('fields'));

    expect($layout['background_path'])->toBe(Storage::disk('public')->path($path))
        ->and($layout['pages'][0]['show_background'])->toBeTrue()
        ->and($layout['pages'][1]['show_background'])->toBeFalse();
});

it('omits hidden fields', function () {
    $template = overlayTemplate('cap_with_note');
    $template->fields()->update(['visible' => false]);
    $layout = (new PrescriptionPrintLayoutBuilder)->build(layoutVisitWithThreeMeds(), $template->fresh('fields'));
    $texts = collect($layout['pages'][0]['elements'])->pluck('text');

    expect($texts->contains(fn ($text) => str_contains($text, 'Ali Khan')))->toBeFalse();
});
