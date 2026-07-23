<?php

use App\Jobs\Tenant\ImportMedicineBrandsJob;
use App\Models\MedicineBrand;
use App\Models\Permission;
use App\Models\User;
use App\Services\MedicineBrandImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Pharmacy Manager',
        'email' => 'pharmacy-brand-import@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('manage pharmacy', 'web');
    $this->user->givePermissionTo('manage pharmacy');

    $this->actingAs($this->user);
});

function writeBrandImportCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'medicine-brand-import-') . '.csv';
    $handle = fopen($path, 'w');

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}

function writeBrandImportXlsx(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'medicine-brand-import-') . '.xlsx';
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

it('imports medicine brands from csv and upserts by name case insensitively', function () {
    $path = writeBrandImportCsv([
        ['name', 'description', 'is_active'],
        ['Getz Pharma', 'Manufacturer', '1'],
        ['BOSCH', 'Manufacturer', '1'],
    ]);

    $result = app(MedicineBrandImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(2)
        ->and($result['updated'])->toBe(0)
        ->and($result['errors'])->toBeEmpty()
        ->and(MedicineBrand::where('name', 'Getz Pharma')->exists())->toBeTrue();

    $updatePath = writeBrandImportCsv([
        ['name', 'description', 'is_active'],
        ['getz pharma', 'Updated manufacturer', '0'],
    ]);

    $updateResult = app(MedicineBrandImportService::class)->importFromFile($updatePath);

    expect($updateResult['created'])->toBe(0)
        ->and($updateResult['updated'])->toBe(1);

    $brand = MedicineBrand::whereRaw('LOWER(name) = ?', ['getz pharma'])->first();

    expect($brand->name)->toBe('getz pharma')
        ->and($brand->description)->toBe('Updated manufacturer')
        ->and($brand->is_active)->toBeFalse();
});

it('imports medicine brands from xlsx files', function () {
    $path = writeBrandImportXlsx([
        ['name', 'description', 'is_active'],
        ['SAAMI', 'Manufacturer', '1'],
    ]);

    $result = app(MedicineBrandImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(1)
        ->and(MedicineBrand::where('name', 'SAAMI')->exists())->toBeTrue();
});

it('returns a readable error when the name column is missing', function () {
    $path = writeBrandImportCsv([
        ['description', 'is_active'],
        ['Manufacturer', '1'],
    ]);

    $result = app(MedicineBrandImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(0)
        ->and($result['updated'])->toBe(0)
        ->and($result['errors'][0])->toContain("'name' column");
});

it('reports row level validation errors without stopping the import', function () {
    $path = writeBrandImportCsv([
        ['name', 'description', 'is_active'],
        ['', 'Missing name', '1'],
        ['CCL', 'Valid brand', '1'],
    ]);

    $result = app(MedicineBrandImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->toHaveCount(1)
        ->and($result['errors'][0])->toContain("'name' is required");
});

it('returns an error when the import file does not exist', function () {
    $result = app(MedicineBrandImportService::class)->importFromFile('/path/that/does/not-exist.csv');

    expect($result['created'])->toBe(0)
        ->and($result['errors'][0])->toContain('File not found');
});

it('accepts csv uploads through the import route', function () {
    $csv = writeBrandImportCsv([
        ['name', 'description', 'is_active'],
        ['ABBOT', 'Manufacturer', '1'],
    ]);

    $response = $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\CheckModule::class,
    ])->post(route('medicine-brands.import'), [
        'file' => new UploadedFile($csv, 'brands.csv', 'text/csv', null, true),
    ]);

    $response->assertRedirect(route('medicine-brands.index'))
        ->assertSessionHas('import_pending', true);
});

it('runs the background import job and stores the result in cache', function () {
    Storage::fake('local');

    $csv = writeBrandImportCsv([
        ['name', 'description', 'is_active'],
        ['HIGH Q', 'Manufacturer', '1'],
    ]);

    $storagePath = 'imports/medicine-brands/test.csv';
    Storage::put($storagePath, file_get_contents($csv));

    $cacheKey = 'medicine-brand-job-key';
    Cache::put($cacheKey, ['status' => 'pending'], now()->addMinutes(30));

    (new ImportMedicineBrandsJob($storagePath, $cacheKey, $this->user->id))
        ->handle(app(MedicineBrandImportService::class));

    expect(Cache::get($cacheKey))->toMatchArray([
        'status' => 'done',
        'created' => 1,
        'updated' => 0,
    ])->and(MedicineBrand::where('name', 'HIGH Q')->exists())->toBeTrue()
        ->and(Storage::exists($storagePath))->toBeFalse();
});

it('returns import status from cache', function () {
    Cache::put('medicine-brand-status-key', [
        'status' => 'done',
        'created' => 2,
        'updated' => 1,
        'errors' => [],
    ], now()->addMinutes(5));

    $response = $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\CheckModule::class,
    ])->get(route('medicine-brands.import-status', ['key' => 'medicine-brand-status-key']));

    $response->assertOk()
        ->assertJson([
            'status' => 'done',
            'created' => 2,
            'updated' => 1,
        ]);

    expect(Cache::get('medicine-brand-status-key'))->toBeNull();
});
