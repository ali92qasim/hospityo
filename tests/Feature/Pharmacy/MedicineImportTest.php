<?php

use App\Jobs\Tenant\ImportMedicinesJob;
use App\Models\Medicine;
use App\Models\MedicineBrand;
use App\Models\MedicineCategory;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\MedicineImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Pharmacy Manager',
        'email' => 'pharmacy-medicine-import@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('manage pharmacy', 'web');
    $this->user->givePermissionTo('manage pharmacy');
    $this->actingAs($this->user);

    $this->category = MedicineCategory::create([
        'code' => 'INJ',
        'name' => 'Injections',
        'is_active' => true,
    ]);

    $this->brand = MedicineBrand::create([
        'name' => 'Getz Pharma',
        'is_active' => true,
    ]);

    $this->unit = Unit::create([
        'name' => 'Injection',
        'abbreviation' => 'INJ',
        'conversion_factor' => 1,
        'type' => 'packaging',
        'is_active' => true,
    ]);
});

function writeMedicineImportCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'medicine-import-') . '.csv';
    $handle = fopen($path, 'w');

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}

function writeMedicineImportXlsx(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'medicine-import-') . '.xlsx';
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

it('builds HMIS ATC-style skus from medicine attributes', function () {
    expect(Medicine::buildSkuFromAttributes('OXIDIL 1GM INJ', '1GM', 'INJ'))->toBe('OXI-1GM-INJ')
        ->and(Medicine::buildSkuFromAttributes('Paracetamol', '500MG', 'TAB', 'GSK', 'Paracetamol'))->toBe('PARACET-500MG-TAB-GSK')
        ->and(Medicine::buildSkuFromAttributes('Paracetamol', '500MG', 'TAB', 'GSK', 'Paracetamol', 'N02BE01'))->toBe('N02BE01-500MG-TAB-GSK')
        ->and(Medicine::uniqueSku('OXI-1GM-INJ', fn () => true))->toBe('OXI-1GM-INJ-001')
        ->and(Medicine::skuPlaceholder())->toBe('N02BE01-500MG-TAB-GSK');
});

it('auto-generates sku on import when sku column is empty', function () {
    $path = writeMedicineImportCsv([
        ['name', 'category_code', 'strength', 'status', 'manage_stock'],
        ['Auto SKU Medicine', 'INJ', '1GM', 'active', '1'],
    ]);

    $result = app(MedicineImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->toBeEmpty();

    $medicine = Medicine::where('name', 'Auto SKU Medicine')->first();

    expect($medicine->sku)->toBe('AUT-1GM-INJ');
});

it('accepts custom sku values without format enforcement', function () {
    $path = writeMedicineImportCsv([
        ['sku', 'name', 'selling_price', 'status', 'manage_stock'],
        ['legacy_sku/001', 'Legacy SKU Medicine', '100', 'active', '1'],
    ]);

    $result = app(MedicineImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(1)
        ->and(Medicine::where('sku', 'legacy_sku/001')->exists())->toBeTrue();
});

it('imports medicines from csv and upserts by sku', function () {
    $path = writeMedicineImportCsv([
        ['sku', 'name', 'generic_name', 'brand_name', 'category_code', 'strength', 'base_unit_abbreviation', 'purchase_unit_abbreviation', 'dispensing_unit_abbreviation', 'selling_price', 'reorder_level', 'status', 'manage_stock'],
        ['2133', 'OXIDIL 1GM INJ', '', 'Getz Pharma', 'INJ', '1GM', 'INJ', 'INJ', 'INJ', '450', '5', 'active', '1'],
    ]);

    $result = app(MedicineImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(1)
        ->and($result['updated'])->toBe(0)
        ->and($result['errors'])->toBeEmpty();

    $medicine = Medicine::where('sku', '2133')->first();

    expect($medicine->name)->toBe('OXIDIL 1GM INJ')
        ->and($medicine->brand_id)->toBe($this->brand->id)
        ->and($medicine->category_id)->toBe($this->category->id)
        ->and($medicine->base_unit_id)->toBe($this->unit->id)
        ->and((float) $medicine->selling_price)->toBe(450.0);

    $updatePath = writeMedicineImportCsv([
        ['sku', 'name', 'generic_name', 'brand_name', 'category_code', 'strength', 'base_unit_abbreviation', 'purchase_unit_abbreviation', 'dispensing_unit_abbreviation', 'selling_price', 'reorder_level', 'status', 'manage_stock'],
        ['2133', 'OXIDIL 1GM INJECTION', '', 'Getz Pharma', 'INJ', '1GM', 'INJ', 'INJ', 'INJ', '500', '8', 'active', '1'],
    ]);

    $updateResult = app(MedicineImportService::class)->importFromFile($updatePath);

    expect($updateResult['created'])->toBe(0)
        ->and($updateResult['updated'])->toBe(1);

    $medicine->refresh();

    expect($medicine->name)->toBe('OXIDIL 1GM INJECTION')
        ->and((float) $medicine->selling_price)->toBe(500.0)
        ->and($medicine->reorder_level)->toBe(8);
});

it('imports medicines from xlsx files', function () {
    $path = writeMedicineImportXlsx([
        ['sku', 'name', 'selling_price', 'status', 'manage_stock'],
        ['SKU-001', 'Sample Medicine', '120', 'active', '1'],
    ]);

    $result = app(MedicineImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(1)
        ->and(Medicine::where('sku', 'SKU-001')->exists())->toBeTrue();
});

it('returns a readable error when required columns are missing', function () {
    $path = writeMedicineImportCsv([
        ['name', 'selling_price'],
        ['Sample Medicine', '100'],
    ]);

    $result = app(MedicineImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(0)
        ->and($result['errors'][0])->toContain("'name' column");
});

it('warns when lookup values are missing but still imports the medicine', function () {
    $path = writeMedicineImportCsv([
        ['sku', 'name', 'brand_name', 'category_code', 'base_unit_abbreviation', 'selling_price', 'status', 'manage_stock'],
        ['9999', 'Unknown Brand Med', 'Missing Brand', 'MISSING', 'ZZZ', '100', 'active', '1'],
    ]);

    $result = app(MedicineImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->toHaveCount(5)
        ->and(Medicine::where('sku', '9999')->value('brand_id'))->toBeNull();
});

it('imports medicines without strength values', function () {
    $path = writeMedicineImportCsv([
        ['sku', 'name', 'category_code', 'selling_price', 'status', 'manage_stock'],
        ['NO-STRENGTH', 'BONIAN INJ', 'INJ', '214', 'active', '1'],
    ]);

    $result = app(MedicineImportService::class)->importFromFile($path);

    expect($result['created'])->toBe(1)
        ->and(Medicine::where('sku', 'NO-STRENGTH')->value('strength'))->toBe('');
});

it('accepts csv uploads through the import route', function () {
    Queue::fake();

    $tenant = new Tenant;
    $tenant->id = 1;
    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    $csv = writeMedicineImportCsv([
        ['sku', 'name', 'selling_price', 'status', 'manage_stock'],
        ['ROUTE-1', 'Route Import Medicine', '75', 'active', '1'],
    ]);

    $response = $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\CheckModule::class,
    ])->post(route('medicines.import'), [
        'file' => new UploadedFile($csv, 'medicines.csv', 'text/csv', null, true),
    ]);

    $response->assertRedirect(route('medicines.index'))
        ->assertSessionHas('import_pending', true);

    Queue::assertPushed(ImportMedicinesJob::class);
});

it('runs the background import job and stores the result in cache', function () {
    Storage::fake('local');

    $csv = writeMedicineImportCsv([
        ['sku', 'name', 'selling_price', 'status', 'manage_stock'],
        ['JOB-1', 'Job Import Medicine', '90', 'active', '1'],
    ]);

    $storagePath = 'imports/medicines/test.csv';
    Storage::put($storagePath, file_get_contents($csv));

    $cacheKey = 'medicine-job-key';
    Cache::put($cacheKey, ['status' => 'pending'], now()->addMinutes(30));

    (new ImportMedicinesJob($storagePath, $cacheKey, $this->user->id, 0))
        ->handle(app(MedicineImportService::class));

    expect(Cache::get($cacheKey))->toMatchArray([
        'status' => 'done',
        'created' => 1,
        'updated' => 0,
    ])->and(Medicine::where('sku', 'JOB-1')->exists())->toBeTrue()
        ->and(Storage::exists($storagePath))->toBeFalse();
});

it('returns import progress while processing', function () {
    Cache::put('medicine-progress-key', [
        'status' => 'processing',
        'processed' => 500,
        'total' => 2000,
        'created' => 400,
        'updated' => 100,
    ], now()->addMinutes(5));

    $response = $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\CheckModule::class,
    ])->get(route('medicines.import-status', ['key' => 'medicine-progress-key']));

    $response->assertOk()
        ->assertJson([
            'status' => 'processing',
            'processed' => 500,
            'total' => 2000,
        ]);

    expect(Cache::get('medicine-progress-key'))->not->toBeNull();
});

it('returns import status from cache', function () {
    Cache::put('medicine-status-key', [
        'status' => 'done',
        'created' => 10,
        'updated' => 5,
        'errors' => [],
    ], now()->addMinutes(5));

    $response = $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\CheckModule::class,
    ])->get(route('medicines.import-status', ['key' => 'medicine-status-key']));

    $response->assertOk()
        ->assertJson([
            'status' => 'done',
            'created' => 10,
            'updated' => 5,
        ]);

    expect(Cache::get('medicine-status-key'))->toBeNull();
});
