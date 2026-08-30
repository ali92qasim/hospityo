<?php

namespace App\Services;

use App\Models\PrescriptionPrintTemplate;
use App\Models\Visit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class PrescriptionPrintService
{
    public function __construct(
        private readonly PrescriptionPrintLayoutBuilder $layoutBuilder,
    ) {}

    public function renderPrescriptionPdf(Visit $visit): ?string
    {
        $template = PrescriptionPrintTemplate::resolvePrintTemplate(
            $visit->attendingDoctor()?->id
        );

        if (! $template) {
            return null;
        }

        $template->loadMissing('fields');

        try {
            $layout = $this->layoutBuilder->build($visit, $template);

            if (
                $template->mode === 'digitized_background'
                && $layout['background_path'] === null
            ) {
                return null;
            }

            return Pdf::loadView('pdf.prescription-print', ['layout' => $layout])
                ->setPaper($this->dompdfSize($layout['paper_size']), $layout['orientation'])
                ->output();
        } catch (\Throwable $e) {
            Log::error('[PrescriptionPrint] PDF render failed', [
                'visit_id' => $visit->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function renderCalibrationPdf(PrescriptionPrintTemplate $template): string
    {
        $layout = $this->layoutBuilder->buildCalibration($template);

        return Pdf::loadView('pdf.prescription-print-calibration', ['layout' => $layout])
            ->setPaper($this->dompdfSize($layout['paper_size']), $layout['orientation'])
            ->output();
    }

    private function dompdfSize(string $paperSize): string
    {
        return strtolower($paperSize) === 'letter' ? 'letter' : 'a4';
    }
}
