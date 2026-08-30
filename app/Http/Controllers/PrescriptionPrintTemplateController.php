<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePrescriptionPrintTemplateRequest;
use App\Http\Requests\UpdatePrescriptionPrintTemplateRequest;
use App\Models\Doctor;
use App\Models\PrescriptionPrintTemplate;
use App\Services\PrescriptionPrintService;
use App\Services\PrescriptionPrintTemplateService;
use App\Support\PrescriptionPrintFieldCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PrescriptionPrintTemplateController extends Controller
{
    public function __construct(
        private readonly PrescriptionPrintTemplateService $templates,
        private readonly PrescriptionPrintService $printer,
    ) {}

    public function index(): View
    {
        $templates = PrescriptionPrintTemplate::query()
            ->with(['doctor', 'fields'])
            ->orderBy('name')
            ->get();

        return view('settings.prescription-print-templates.index', [
            'templates' => $templates,
            'completeness' => $templates->mapWithKeys(
                fn (PrescriptionPrintTemplate $template) => [
                    $template->id => $this->templates->isComplete($template),
                ]
            ),
        ]);
    }

    public function create(): View
    {
        return view('settings.prescription-print-templates.create', [
            'doctors' => Doctor::query()->orderBy('name')->get(),
            'fields' => $this->defaultFields(),
        ]);
    }

    public function store(StorePrescriptionPrintTemplateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated): void {
            $attributes = Arr::except($validated, ['background_image', 'fields']);

            if ($request->hasFile('background_image')) {
                $attributes['background_image_path'] = $request->file('background_image')
                    ->store(tenant_storage_path('prescription-print-templates'), 'public');
            }

            $template = PrescriptionPrintTemplate::create($attributes);
            $this->templates->seedDefaultFields($template);
            $this->updateFields($template, $validated['fields'] ?? []);
        });

        return redirect()
            ->route('settings.prescription-print-templates.index')
            ->with('success', 'Prescription print template created.');
    }

    public function edit(PrescriptionPrintTemplate $prescriptionPrintTemplate): View
    {
        $prescriptionPrintTemplate->load('fields');

        return view('settings.prescription-print-templates.edit', [
            'template' => $prescriptionPrintTemplate,
            'doctors' => Doctor::query()->orderBy('name')->get(),
            'fields' => $prescriptionPrintTemplate->fields->keyBy('field_key'),
        ]);
    }

    public function update(
        UpdatePrescriptionPrintTemplateRequest $request,
        PrescriptionPrintTemplate $prescriptionPrintTemplate,
    ): RedirectResponse {
        $validated = $request->validated();
        $oldImagePath = $prescriptionPrintTemplate->background_image_path;

        DB::transaction(function () use ($request, $validated, $prescriptionPrintTemplate): void {
            $attributes = Arr::except($validated, ['background_image', 'fields']);

            if ($request->hasFile('background_image')) {
                $attributes['background_image_path'] = $request->file('background_image')
                    ->store(tenant_storage_path('prescription-print-templates'), 'public');
            }

            $prescriptionPrintTemplate->update($attributes);
            $this->updateFields($prescriptionPrintTemplate, $validated['fields'] ?? []);
        });

        if ($request->hasFile('background_image') && $oldImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return redirect()
            ->route('settings.prescription-print-templates.index')
            ->with('success', 'Prescription print template updated.');
    }

    public function destroy(PrescriptionPrintTemplate $prescriptionPrintTemplate): RedirectResponse
    {
        $imagePath = $prescriptionPrintTemplate->background_image_path;
        $prescriptionPrintTemplate->delete();

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        return redirect()
            ->route('settings.prescription-print-templates.index')
            ->with('success', 'Prescription print template deleted.');
    }

    public function activate(PrescriptionPrintTemplate $prescriptionPrintTemplate): RedirectResponse
    {
        $this->templates->activate($prescriptionPrintTemplate);

        return redirect()
            ->route('settings.prescription-print-templates.index')
            ->with('success', 'Prescription print template activated.');
    }

    public function deactivate(PrescriptionPrintTemplate $prescriptionPrintTemplate): RedirectResponse
    {
        $this->templates->deactivate($prescriptionPrintTemplate);

        return redirect()
            ->route('settings.prescription-print-templates.index')
            ->with('success', 'Prescription print template deactivated.');
    }

    public function calibration(PrescriptionPrintTemplate $prescriptionPrintTemplate): Response|RedirectResponse
    {
        if ($prescriptionPrintTemplate->mode !== 'overlay_physical') {
            return redirect()
                ->route('settings.prescription-print-templates.index')
                ->withErrors(['mode' => 'Calibration sheets are only available for physical overlay templates.']);
        }

        return response($this->printer->renderCalibrationPdf($prescriptionPrintTemplate), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="prescription-template-calibration.pdf"',
        ]);
    }

    /** @return array<string, array<string, float|string|bool>> */
    private function defaultFields(): array
    {
        $fields = [];
        $y = 20.0;

        foreach (PrescriptionPrintFieldCatalog::keys() as $key) {
            $fields[$key] = [
                'x_mm' => 15.0,
                'y_mm' => $y,
                'font_size' => 10.0,
                'font_weight' => in_array($key, ['patient_name', 'doctor_name'], true) ? 'bold' : 'normal',
                'align' => 'left',
                'visible' => ! str_starts_with($key, 'hospital_'),
            ];
            $y += 6;
        }

        return $fields;
    }

    /** @param array<string, array<string, mixed>> $fields */
    private function updateFields(PrescriptionPrintTemplate $template, array $fields): void
    {
        foreach ($fields as $key => $attributes) {
            if (! in_array($key, PrescriptionPrintFieldCatalog::keys(), true)) {
                continue;
            }

            $template->fields()->updateOrCreate(
                ['field_key' => $key],
                Arr::only($attributes, ['x_mm', 'y_mm', 'font_size', 'font_weight', 'align', 'visible'])
            );
        }
    }
}
