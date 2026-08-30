<?php

namespace App\Services;

use App\Models\PrescriptionPrintTemplate;
use App\Support\PrescriptionPrintFieldCatalog;
use Illuminate\Validation\ValidationException;

class PrescriptionPrintTemplateService
{
    public function isComplete(PrescriptionPrintTemplate $template): bool
    {
        $template->loadMissing('fields');

        $visible = $template->fields
            ->where('visible', true)
            ->keyBy('field_key');

        foreach (PrescriptionPrintFieldCatalog::coreKeys() as $key) {
            $field = $visible->get($key);
            if (! $field) {
                return false;
            }
        }

        return true;
    }

    public function activate(PrescriptionPrintTemplate $template): void
    {
        if (! $this->isComplete($template)) {
            throw ValidationException::withMessages([
                'is_active' => 'Position patient name, age, date, and doctor name before marking this template active.',
            ]);
        }

        if ($template->mode === 'digitized_background' && blank($template->background_image_path)) {
            throw ValidationException::withMessages([
                'background_image_path' => 'A background image is required for digitized letterhead templates.',
            ]);
        }

        $this->deactivateActiveSiblings($template);

        $template->update(['is_active' => true]);
    }

    public function deactivateActiveSiblings(PrescriptionPrintTemplate $template): void
    {
        PrescriptionPrintTemplate::query()
            ->where('id', '!=', $template->id)
            ->where('is_active', true)
            ->when(
                $template->doctor_id === null,
                fn ($query) => $query->whereNull('doctor_id'),
                fn ($query) => $query->where('doctor_id', $template->doctor_id),
            )
            ->update(['is_active' => false]);
    }

    public function deactivate(PrescriptionPrintTemplate $template): void
    {
        $template->update(['is_active' => false]);
    }

    public function seedDefaultFields(PrescriptionPrintTemplate $template): void
    {
        $y = 20.0;
        foreach (PrescriptionPrintFieldCatalog::keys() as $key) {
            $isHospitalChrome = str_starts_with($key, 'hospital_');
            $template->fields()->create([
                'field_key' => $key,
                'x_mm' => 15,
                'y_mm' => $y,
                'font_size' => 10,
                'font_weight' => $key === 'patient_name' || $key === 'doctor_name' ? 'bold' : 'normal',
                'align' => 'left',
                'visible' => ! $isHospitalChrome,
            ]);
            $y += 6;
        }
    }
}
