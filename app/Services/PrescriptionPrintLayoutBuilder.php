<?php

namespace App\Services;

use App\Models\PrescriptionPrintTemplate;
use App\Models\Visit;
use App\Support\PrescriptionPrintPaper;
use Illuminate\Support\Facades\Storage;

class PrescriptionPrintLayoutBuilder
{
    private PrescriptionPrintFieldResolver $resolver;

    public function __construct(?PrescriptionPrintFieldResolver $resolver = null)
    {
        $this->resolver = $resolver ?? new PrescriptionPrintFieldResolver;
    }

    public function build(Visit $visit, PrescriptionPrintTemplate $template): array
    {
        $template->loadMissing('fields');
        $visit->loadMissing([
            'prescriptions.items.medicine',
            'prescriptions.items.prescriptionInstruction',
        ]);

        [$width, $height] = PrescriptionPrintPaper::dimensionsMm(
            $template->paper_size,
            $template->orientation,
        );

        $backgroundPath = $this->backgroundPath($template);
        $pages = [[
            'show_background' => $backgroundPath !== null,
            'elements' => $this->fieldElements($visit, $template),
        ]];

        $this->addPrescriptionRows($pages, $this->prescriptionLines($visit), $template);

        return [
            'paper_size' => $template->paper_size,
            'orientation' => $template->orientation,
            'width_mm' => $width,
            'height_mm' => $height,
            'background_path' => $backgroundPath,
            'pages' => $pages,
        ];
    }

    public function buildCalibration(PrescriptionPrintTemplate $template): array
    {
        $template->loadMissing('fields');

        [$width, $height] = PrescriptionPrintPaper::dimensionsMm(
            $template->paper_size,
            $template->orientation,
        );

        $elements = [];

        for ($x = 10; $x < $width; $x += 10) {
            $elements[] = $this->element((string) $x, (float) $x, 5.0, 6.0);
        }

        for ($y = 10; $y < $height; $y += 10) {
            $elements[] = $this->element((string) $y, 5.0, (float) $y, 6.0);
        }

        foreach ($template->fields->where('visible', true) as $field) {
            $elements[] = $this->element(
                '+ '.$field->field_key,
                (float) $field->x_mm,
                (float) $field->y_mm,
                8.0,
            );
        }

        return [
            'paper_size' => $template->paper_size,
            'orientation' => $template->orientation,
            'width_mm' => $width,
            'height_mm' => $height,
            'background_path' => null,
            'pages' => [[
                'show_background' => false,
                'elements' => $elements,
            ]],
        ];
    }

    private function fieldElements(Visit $visit, PrescriptionPrintTemplate $template): array
    {
        $elements = [];

        foreach ($template->fields->where('visible', true) as $field) {
            $value = $this->resolver->resolve($visit, $field->field_key);

            if ($value === null || trim($value) === '') {
                continue;
            }

            $elements[] = $this->element(
                $value,
                (float) $field->x_mm,
                (float) $field->y_mm,
                (float) $field->font_size,
                $field->font_weight,
                $field->align,
            );
        }

        return $elements;
    }

    private function prescriptionLines(Visit $visit): array
    {
        $lines = [];
        $number = 1;

        foreach ($visit->prescriptions as $prescription) {
            foreach ($prescription->items as $item) {
                if (! $item->medicine?->name) {
                    continue;
                }

                $text = $number++.'. '.$item->medicine->name;
                $instruction = $item->prescriptionInstruction?->instruction;

                if ($instruction) {
                    $text .= ' — '.$instruction;
                }

                if ((float) $item->quantity > 1) {
                    $text .= ' — Qty: '.$item->quantity;
                }

                $lines[] = $text;
            }
        }

        return $lines;
    }

    private function addPrescriptionRows(array &$pages, array $lines, PrescriptionPrintTemplate $template): void
    {
        if ($lines === []) {
            return;
        }

        $maxRows = max(1, (int) $template->rx_max_rows);
        $rowHeight = (float) $template->rx_row_height;
        $startY = (float) $template->rx_start_y;

        if ($template->rx_overflow_policy === 'second_page_plain') {
            foreach (array_chunk($lines, $maxRows) as $pageIndex => $pageLines) {
                if ($pageIndex > 0) {
                    $pages[] = ['show_background' => false, 'elements' => []];
                }

                $this->appendRows($pages[$pageIndex]['elements'], $pageLines, $startY, $rowHeight);
            }

            return;
        }

        if ($template->rx_overflow_policy === 'shrink_font') {
            $spacing = count($lines) > $maxRows
                ? ($rowHeight * $maxRows) / count($lines)
                : $rowHeight;
            $fontSize = count($lines) > $maxRows
                ? max(6.0, 11.0 * $maxRows / count($lines))
                : 11.0;

            $this->appendRows($pages[0]['elements'], $lines, 0.0, $spacing, $fontSize);

            return;
        }

        if (count($lines) > $maxRows) {
            $lines = array_slice($lines, 0, max(0, $maxRows - 1));
            $lines[] = 'continued — see attached';
        }

        $this->appendRows($pages[0]['elements'], $lines, $startY, $rowHeight);
    }

    private function appendRows(
        array &$elements,
        array $lines,
        float $startY,
        float $spacing,
        float $fontSize = 11.0,
    ): void {
        foreach ($lines as $index => $line) {
            $elements[] = $this->element(
                $line,
                20.0,
                $startY + ($index * $spacing),
                $fontSize,
            );
        }
    }

    private function backgroundPath(PrescriptionPrintTemplate $template): ?string
    {
        $path = $template->background_image_path;

        if (
            $template->mode !== 'digitized_background'
            || ! $path
            || ! Storage::disk('public')->exists($path)
        ) {
            return null;
        }

        return Storage::disk('public')->path($path);
    }

    private function element(
        string $text,
        float $x,
        float $y,
        float $fontSize,
        string $fontWeight = 'normal',
        string $align = 'left',
    ): array {
        return [
            'text' => $text,
            'x_mm' => $x,
            'y_mm' => $y,
            'font_size' => $fontSize,
            'font_weight' => $fontWeight,
            'align' => $align,
        ];
    }
}
