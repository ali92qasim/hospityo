<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;

/**
 * Reads CSV and Excel (.xls, .xlsx) files into row arrays for import services.
 */
class SpreadsheetImportReader
{
    /**
     * @return array<int, array<int, string>>
     *
     * @throws \RuntimeException
     */
    public function read(string $path): array
    {
        if (! is_file($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }

        if ($this->isExcel($path)) {
            return $this->readExcel($path);
        }

        return $this->readCsv($path);
    }

    private function isExcel(string $path): bool
    {
        $excelMimes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/zip',
            'application/octet-stream',
        ];

        $mime = @mime_content_type($path) ?: '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($mime, $excelMimes, true) || in_array($ext, ['xlsx', 'xls'], true);
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readCsv(string $path): array
    {
        $handle = @fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Could not open the CSV file for reading.');
        }

        $rows = [];

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not parse the CSV file. Please verify the file format.', 0, $e);
        } finally {
            fclose($handle);
        }

        if (! empty($rows[0][0])) {
            $rows[0][0] = ltrim((string) $rows[0][0], "\xEF\xBB\xBF");
        }

        return $rows;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readExcel(string $path): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $label = $ext === 'xls'
            ? '.xls (Excel 97–2003)'
            : '.xlsx (Excel 2007+)';

        try {
            $reader = IOFactory::createReaderForFile($path);

            if (method_exists($reader, 'setReadDataOnly')) {
                $reader->setReadDataOnly(true);
            }

            $spreadsheet = $reader->load($path);
        } catch (ReaderException $e) {
            throw new \RuntimeException(
                "Could not read the {$label} file. Please verify it is a valid Excel workbook.",
                0,
                $e
            );
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Could not read the Excel file. It may be corrupt, password-protected, or in an unsupported format.',
                0,
                $e
            );
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($sheet->getRowIterator() as $row) {
            $cells = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                $cells[] = (string) ($cell->getCalculatedValue() ?? '');
            }

            $rows[] = $cells;
        }

        if ($rows === []) {
            throw new \RuntimeException('The Excel file appears to be empty.');
        }

        return $rows;
    }
}
