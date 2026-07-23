<?php

use App\Jobs\Tenant\ImportOpeningStockJob;
use App\Models\InventoryTransaction;
use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\OpeningStockImportService;
use App\Services\OpeningStockService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Inventory Manager',
        'email' => 'opening-stock-import@example.com',
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

    $this->unit = Unit::create([
        'name' => 'Injection',
        'abbreviation' => 'INJ',
        'conversion_factor' => 1,
        'type' => 'packaging',
        'is_active' => true,
    ]);

    $this->medicine = Medicine::create([
        'name' => 'OXIDIL 1GM INJ',
        'sku' => 'OXI-1GM-INJ',
        'category_id' => $this->category->id,
        'strength' => '1GM',
        'selling_price' => 450,
        'base_unit_id' => $this->unit->id,
        'purchase_unit_id' => $this->unit->id,
        'dispensing_unit_id' => $this->unit->id,
        'reorder_level' => 5,
        'status' => 'active',
        'manage_stock' => true,
    ]);
});

function writeOpeningStockImportCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'opening-stock-import-') . '.csv';
    $handle = fopen($path, 'w');

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}

it('imports opening stock from csv and creates stock_in transactions', function () {
    $path = writeOpeningStockImportCsv([
        ['sku', 'quantity', 'unit_abbreviation', 'unit_cost', 'batch_no', 'expiry_date'],
        ['OXI-1GM-INJ', '100', 'INJ', '4.50', 'OPEN-001', '2027-06-30'],
    ]);

    $result = app(OpeningStockImportService::class)->importFromFile($path, $this->user->id);

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->toBeEmpty()
        ->and(OpeningStockService::isLocked())->toBeTrue();

    $transaction = InventoryTransaction::where('medicine_id', $this->medicine->id)->first();

    expect($transaction->type)->toBe('stock_in')
        ->and($transaction->quantity)->toBe(100)
        ->and($transaction->remaining_quantity)->toBe(100)
        ->and($transaction->batch_no)->toBe('OPEN-001')
        ->and($transaction->supplier)->toBe(OpeningStockService::DEFAULT_SUPPLIER)
        ->and($transaction->notes)->toBe(OpeningStockService::DEFAULT_NOTES);
});

it('locks the clinic after a successful import', function () {
    $path = writeOpeningStockImportCsv([
        ['sku', 'quantity', 'unit_abbreviation', 'unit_cost', 'batch_no', 'expiry_date'],
        ['OXI-1GM-INJ', '10', 'INJ', '5', 'BATCH-A', '2027-01-01'],
    ]);

    app(OpeningStockImportService::class)->importFromFile($path, $this->user->id);

    $status = OpeningStockService::status();

    expect($status['locked'])->toBeTrue()
        ->and($status['imported_by'])->toBe('Inventory Manager')
        ->and($status['batch_count'])->toBe(1);
});

it('rejects a second import once opening stock is locked', function () {
    OpeningStockService::lock($this->user->id, 1);

    $path = writeOpeningStockImportCsv([
        ['sku', 'quantity', 'unit_abbreviation', 'unit_cost', 'batch_no', 'expiry_date'],
        ['OXI-1GM-INJ', '10', 'INJ', '5', 'BATCH-B', '2027-01-01'],
    ]);

    $result = app(OpeningStockImportService::class)->importFromFile($path, $this->user->id);

    expect($result['created'])->toBe(0)
        ->and($result['errors'][0])->toContain('already been imported')
        ->and(InventoryTransaction::count())->toBe(0);
});

it('aborts the entire import when any row is invalid', function () {
    $path = writeOpeningStockImportCsv([
        ['sku', 'quantity', 'unit_abbreviation', 'unit_cost', 'batch_no', 'expiry_date'],
        ['OXI-1GM-INJ', '10', 'INJ', '5', 'VALID-BATCH', '2027-01-01'],
        ['UNKNOWN-SKU', '5', 'INJ', '5', 'BAD-BATCH', '2027-01-01'],
    ]);

    $result = app(OpeningStockImportService::class)->importFromFile($path, $this->user->id);

    expect($result['created'])->toBe(0)
        ->and($result['errors'])->not->toBeEmpty()
        ->and(InventoryTransaction::count())->toBe(0)
        ->and(OpeningStockService::isLocked())->toBeFalse();
});

it('returns a readable error when required columns are missing', function () {
    $path = writeOpeningStockImportCsv([
        ['sku', 'quantity'],
        ['OXI-1GM-INJ', '10'],
    ]);

    $result = app(OpeningStockImportService::class)->importFromFile($path, $this->user->id);

    expect($result['created'])->toBe(0)
        ->and($result['errors'][0])->toContain('Invalid file format');
});

it('accepts csv uploads through the import route', function () {
    Queue::fake();

    $tenant = new Tenant;
    $tenant->id = 1;
    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    $csv = writeOpeningStockImportCsv([
        ['sku', 'quantity', 'unit_abbreviation', 'unit_cost', 'batch_no', 'expiry_date'],
        ['OXI-1GM-INJ', '25', 'INJ', '4.50', 'ROUTE-001', '2027-12-31'],
    ]);

    $response = $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\CheckModule::class,
    ])->post(route('inventory.opening-stock.import'), [
        'file' => new UploadedFile($csv, 'opening-stock.csv', 'text/csv', null, true),
    ]);

    $response->assertRedirect(route('inventory.opening-stock'))
        ->assertSessionHas('import_pending', true);

    Queue::assertPushed(ImportOpeningStockJob::class);
});

it('blocks import through the route when already locked', function () {
    OpeningStockService::lock($this->user->id, 3);

    $csv = writeOpeningStockImportCsv([
        ['sku', 'quantity', 'unit_abbreviation', 'unit_cost', 'batch_no', 'expiry_date'],
        ['OXI-1GM-INJ', '25', 'INJ', '4.50', 'ROUTE-002', '2027-12-31'],
    ]);

    $response = $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\CheckModule::class,
    ])->post(route('inventory.opening-stock.import'), [
        'file' => new UploadedFile($csv, 'opening-stock.csv', 'text/csv', null, true),
    ]);

    $response->assertRedirect(route('inventory.opening-stock'))
        ->assertSessionHas('error');
});

it('processes the background job and stores the result in cache', function () {
    Storage::fake('local');

    $path = writeOpeningStockImportCsv([
        ['sku', 'quantity', 'unit_abbreviation', 'unit_cost', 'batch_no', 'expiry_date'],
        ['OXI-1GM-INJ', '40', 'INJ', '3.25', 'JOB-001', '2028-03-01'],
    ]);

    $storagePath = 'imports/opening-stock/test.csv';
    Storage::put($storagePath, file_get_contents($path));

    $cacheKey = 'opening_stock_import_test';
    Cache::put($cacheKey, ['status' => 'pending'], now()->addMinutes(30));

    $job = new ImportOpeningStockJob($storagePath, $cacheKey, $this->user->id, 0);
    $job->handle(app(OpeningStockImportService::class));

    $result = Cache::get($cacheKey);

    expect($result['status'])->toBe('done')
        ->and($result['created'])->toBe(1)
        ->and($result['locked'])->toBeTrue()
        ->and(OpeningStockService::isLocked())->toBeTrue()
        ->and(InventoryTransaction::count())->toBe(1);
});

it('shows the opening stock page with template download when unlocked', function () {
    $response = $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\CheckModule::class,
    ])->get(route('inventory.opening-stock'));

    $response->assertOk()
        ->assertSee('Opening Stock Import')
        ->assertSee('Template')
        ->assertSee('Import Opening Stock');
});

it('shows locked state on the opening stock page after import', function () {
    OpeningStockService::lock($this->user->id, 12);

    $response = $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\CheckModule::class,
    ])->get(route('inventory.opening-stock'));

    $response->assertOk()
        ->assertSee('Opening stock import completed')
        ->assertDontSee('Import Opening Stock');
});
