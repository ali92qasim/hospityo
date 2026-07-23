<?php

use App\Jobs\Tenant\ImportUnitsJob;
use App\Models\Permission;
use App\Models\Unit;
use App\Models\User;
use App\Services\UnitImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Pharmacy Manager',
        'email' => 'pharmacy-unit-import@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('manage pharmacy', 'web');
    $this->user->givePermissionTo('manage pharmacy');
    $this->actingAs($this->user);
});

function writeUnitImportCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'unit-import-') . '.csv';
    $handle = fopen($path, 'w');

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}

function writeUnitImportXlsx(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'unit-import-') . '.xlsx';
    $sheet = new Spreadsheet();
    $activeSheet = $sheet->getActiveSheet();

    foreach ($rows as $rowIndex => $row) {
        foreach ($row as $columnIndex => $value) {
            $activeSheet->setCellValue(chr(65 + $columnIndex) . ($rowIndex + 1), $value);
        }
    }

    (new Xlsx($sheet))->save($path);

    return $path;
}

it('imports base units from csv and upserts by abbreviation', function () {
    $path = writeUnitImportCsv([
        ['name', 'abbreviation', 'conversion_factor', 'type', 'is_active'],
        ['Injection', 'INJ', '1', 'packaging', '1'],
        ['Miscellaneous', 'MISC.', '1', 'packaging', '1'],
    ]);

    $result = app(UnitImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(2)
        ->and($result['updated'])->toBe(0)
        ->and($result['errors'])->toBeEmpty()
        ->and(Unit::where('abbreviation', 'INJ')->value('name'))->toBe('Injection');

    $updatePath = writeUnitImportCsv([
        ['name', 'abbreviation', 'conversion_factor', 'type', 'is_active'],
        ['Injection Unit', 'inj', '1', 'packaging', '0'],
    ]);

    $updateResult = app(UnitImportService::class)->importFromFile($updatePath);

    expect($updateResult['created'])->toBe(0)
        ->and($updateResult['updated'])->toBe(1);

    $unit = Unit::whereRaw('UPPER(abbreviation) = ?', ['INJ'])->first();

    expect($unit->name)->toBe('Injection Unit')
        ->and($unit->is_active)->toBeFalse();
});

it('imports derived units using base unit in parentheses', function () {
    Unit::create([
        'name' => 'INJECTION',
        'abbreviation' => 'INJ',
        'conversion_factor' => 1,
        'type' => 'packaging',
        'is_active' => true,
    ]);

    $path = writeUnitImportCsv([
        ['name', 'abbreviation', 'conversion_factor', 'type', 'is_active'],
        ['INJ PACKING 10 (INJ)', 'INJ P10', '10', 'packaging', '1'],
    ]);

    $result = app(UnitImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->toBeEmpty();

    $unit = Unit::where('abbreviation', 'INJ P10')->first();

    expect($unit->baseUnit->abbreviation)->toBe('INJ')
        ->and((float) $unit->conversion_factor)->toBe(10.0);
});

it('infers conversion factor from natt brothers style names', function () {
    Unit::create([
        'name' => 'MISC.',
        'abbreviation' => 'MISC.',
        'conversion_factor' => 1,
        'type' => 'packaging',
        'is_active' => true,
    ]);

    $path = writeUnitImportCsv([
        ['Name', 'Short name', 'Allow decimal'],
        ['10 UNITS (10MISC.)', '10U', 'Yes'],
    ]);

    $result = app(UnitImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->toBeEmpty();

    $unit = Unit::where('abbreviation', '10U')->first();

    expect($unit->baseUnit->abbreviation)->toBe('MISC.')
        ->and((float) $unit->conversion_factor)->toBe(10.0);
});

it('imports the natt brothers pharmacy reference workbook', function () {
    $referencePath = public_path('templates/Units - Natt Brothers Pharmacy.xlsx');

    $result = app(UnitImportService::class)->importFromFile($referencePath);

    expect($result['created'])->toBeGreaterThan(28)
        ->and($result['errors'])->toBeEmpty()
        ->and(Unit::where('abbreviation', 'INJ')->exists())->toBeTrue()
        ->and(Unit::where('abbreviation', 'MISC.')->exists())->toBeTrue()
        ->and(Unit::where('abbreviation', '10U')->exists())->toBeTrue()
        ->and(Unit::where('abbreviation', 'TAB/CAP')->exists())->toBeTrue()
        ->and(Unit::where('abbreviation', 'SACHET')->exists())->toBeTrue()
        ->and(Unit::where('abbreviation', 'SUS/SYP')->exists())->toBeTrue()
        ->and(Unit::where('abbreviation', 'P5')->exists())->toBeTrue()
        ->and(Unit::where('abbreviation', 'P50')->exists())->toBeTrue();
});

it('medicine units template covers every unit in the natt brothers reference file', function () {
    $templatePath = public_path('templates/medicine-units-template.csv');

    $templateAbbrevs = [];
    $handle = fopen($templatePath, 'r');
    fgetcsv($handle, 0, ',', '"', '');
    while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
        $templateAbbrevs[strtoupper(trim($row[1] ?? ''))] = trim($row[0] ?? '');
    }
    fclose($handle);

    $expectedAbbrevs = [
        'MISC.', 'INJ', 'TAB/CAP', 'SACHET', 'SUS/SYP', 'DROPS',
        '5U', '10U', '12U', '14U', '20U', '25U', '28U', '30U', '50U', '100U',
        'INJ P5', 'INJ P6', 'INJ P10', 'INJ P25', 'INJ P100',
        'SP5', 'SP 10',
        'P5', 'P6', 'P10', 'T14', 'P20', 'P28', 'P30', 'P50', 'P100', 'P200',
    ];

    foreach ($expectedAbbrevs as $abbrev) {
        expect($templateAbbrevs)->toHaveKey(strtoupper($abbrev));
    }

    expect(count($templateAbbrevs))->toBe(count($expectedAbbrevs));
});

it('imports units from xlsx files', function () {
    $path = writeUnitImportXlsx([
        ['name', 'abbreviation', 'conversion_factor', 'type', 'is_active'],
        ['Drops', 'DRP', '1', 'liquid', '1'],
    ]);

    $result = app(UnitImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(1)
        ->and(Unit::where('abbreviation', 'DRP')->exists())->toBeTrue();
});

it('returns a readable error when required columns are missing', function () {
    $path = writeUnitImportCsv([
        ['name', 'type'],
        ['Sample Unit', 'solid'],
    ]);

    $result = app(UnitImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(0)
        ->and($result['errors'][0])->toContain("'abbreviation'");
});

it('accepts csv uploads through the import route', function () {
    Queue::fake();

    $csv = writeUnitImportCsv([
        ['name', 'abbreviation', 'conversion_factor', 'type', 'is_active'],
        ['Piece', 'PC', '1', 'solid', '1'],
    ]);

    $response = $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\CheckModule::class,
    ])->post(route('units.import'), [
        'file' => new UploadedFile($csv, 'units.csv', 'text/csv', null, true),
    ]);

    $response->assertRedirect(route('units.index'))
        ->assertSessionHas('import_pending', true);

    Queue::assertPushed(ImportUnitsJob::class);
});

it('processes the background job and stores the result in cache', function () {
    Storage::fake('local');

    $path = writeUnitImportCsv([
        ['name', 'abbreviation', 'conversion_factor', 'type', 'is_active'],
        ['Sachet', 'SACH', '1', 'packaging', '1'],
    ]);

    $storagePath = 'imports/units/test.csv';
    Storage::put($storagePath, file_get_contents($path));

    $cacheKey = 'unit_import_test';
    Cache::put($cacheKey, ['status' => 'pending'], now()->addMinutes(30));

    $job = new ImportUnitsJob($storagePath, $cacheKey, $this->user->id);
    $job->handle(app(UnitImportService::class));

    $result = Cache::get($cacheKey);

    expect($result['status'])->toBe('done')
        ->and($result['created'])->toBe(1)
        ->and(Unit::where('abbreviation', 'SACH')->exists())->toBeTrue();
});

it('shows the units page with template download', function () {
    $response = $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\CheckModule::class,
    ])->get(route('units.index'));

    $response->assertOk()
        ->assertSee('Template')
        ->assertSee('Import CSV / Excel');
});
