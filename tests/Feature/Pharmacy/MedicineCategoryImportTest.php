<?php

use App\Jobs\Tenant\ImportMedicineCategoriesJob;
use App\Models\MedicineCategory;
use App\Models\Permission;
use App\Models\User;
use App\Services\MedicineCategoryImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Pharmacy Manager',
        'email' => 'pharmacy-import@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('manage pharmacy', 'web');
    $this->user->givePermissionTo('manage pharmacy');

    $this->actingAs($this->user);
});

function writeImportCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'medicine-category-import-') . '.csv';
    $handle = fopen($path, 'w');

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}

function writeImportXlsx(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'medicine-category-import-') . '.xlsx';
    $sheet = new Spreadsheet();
    $activeSheet = $sheet->getActiveSheet();

    foreach ($rows as $rowIndex => $row) {
        foreach ($row as $columnIndex => $value) {
            $column = chr(65 + $columnIndex);
            $activeSheet->setCellValue($column . ($rowIndex + 1), $value);
        }
    }

    (new Xlsx($sheet))->save($path);

    return $path;
}

it('imports medicine categories from csv and upserts by code', function () {
    $path = writeImportCsv([
        ['code', 'name', 'description', 'is_active'],
        ['TAB_CAP', 'Tablets & Capsules', 'Oral solids', '1'],
        ['INJ', 'Injections', 'Injectable meds', '1'],
    ]);

    $result = app(MedicineCategoryImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(2)
        ->and($result['updated'])->toBe(0)
        ->and($result['errors'])->toBeEmpty()
        ->and(MedicineCategory::where('code', 'TAB_CAP')->value('name'))->toBe('Tablets & Capsules');

    $updatePath = writeImportCsv([
        ['code', 'name', 'description', 'is_active'],
        ['tab_cap', 'Tablets and Capsules', 'Updated description', '0'],
    ]);

    $updateResult = app(MedicineCategoryImportService::class)->importFromFile($updatePath);

    expect($updateResult['created'])->toBe(0)
        ->and($updateResult['updated'])->toBe(1);

    $category = MedicineCategory::where('code', 'TAB_CAP')->first();

    expect($category->name)->toBe('Tablets and Capsules')
        ->and($category->description)->toBe('Updated description')
        ->and($category->is_active)->toBeFalse();
});

it('imports medicine categories from xlsx files', function () {
    $path = writeImportXlsx([
        ['code', 'name', 'description', 'is_active'],
        ['SYP', 'Syrups & Suspensions', 'Liquid oral meds', '1'],
    ]);

    $result = app(MedicineCategoryImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(1)
        ->and(MedicineCategory::where('code', 'SYP')->exists())->toBeTrue();
});

it('returns a readable error when required columns are missing', function () {
    $path = writeImportCsv([
        ['name', 'description'],
        ['Tablets', 'Oral solids'],
    ]);

    $result = app(MedicineCategoryImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(0)
        ->and($result['updated'])->toBe(0)
        ->and($result['errors'][0])->toContain("'code' and 'name' columns");
});

it('reports row level validation errors without stopping the import', function () {
    $path = writeImportCsv([
        ['code', 'name', 'description', 'is_active'],
        ['', 'Missing Code', 'Should fail', '1'],
        ['DROPS', 'Drops & Topical', 'Valid row', '1'],
    ]);

    $result = app(MedicineCategoryImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->toHaveCount(1)
        ->and($result['errors'][0])->toContain("'code' is required");
});

it('returns an error when the import file does not exist', function () {
    $result = app(MedicineCategoryImportService::class)->importFromFile('/path/that/does/not-exist.csv');

    expect($result['created'])->toBe(0)
        ->and($result['errors'][0])->toContain('File not found');
});

it('accepts csv uploads through the import route', function () {
    $csv = writeImportCsv([
        ['code', 'name', 'description', 'is_active'],
        ['MISC', 'Miscellaneous', 'Other items', '1'],
    ]);

    $response = $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\CheckModule::class,
    ])->post(route('medicine-categories.import'), [
        'file' => new UploadedFile($csv, 'categories.csv', 'text/csv', null, true),
    ]);

    $response->assertRedirect(route('medicine-categories.index'))
        ->assertSessionHas('import_pending', true);
});

it('runs the background import job and stores the result in cache', function () {
    Storage::fake('local');

    $csv = writeImportCsv([
        ['code', 'name', 'description', 'is_active'],
        ['SACHET', 'Sachets', 'Single dose packs', '1'],
    ]);

    $storagePath = 'imports/medicine-categories/test.csv';
    Storage::put($storagePath, file_get_contents($csv));

    $cacheKey = 'medicine-category-job-key';
    Cache::put($cacheKey, ['status' => 'pending'], now()->addMinutes(30));

    (new ImportMedicineCategoriesJob($storagePath, $cacheKey, $this->user->id))
        ->handle(app(MedicineCategoryImportService::class));

    expect(Cache::get($cacheKey))->toMatchArray([
        'status' => 'done',
        'created' => 1,
        'updated' => 0,
    ])->and(MedicineCategory::where('code', 'SACHET')->exists())->toBeTrue()
        ->and(Storage::exists($storagePath))->toBeFalse();
});

it('returns import status from cache', function () {
    Cache::put('medicine-category-status-key', [
        'status' => 'done',
        'created' => 2,
        'updated' => 1,
        'errors' => [],
    ], now()->addMinutes(5));

    $response = $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\CheckModule::class,
    ])->get(route('medicine-categories.import-status', ['key' => 'medicine-category-status-key']));

    $response->assertOk()
        ->assertJson([
            'status' => 'done',
            'created' => 2,
            'updated' => 1,
        ]);

    expect(Cache::get('medicine-category-status-key'))->toBeNull();
});
