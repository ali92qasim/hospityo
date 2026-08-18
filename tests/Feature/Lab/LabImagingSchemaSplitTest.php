<?php

use App\Models\Account;
use App\Services\LabImagingSchemaSplit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('replaces investigations with lab_tests and imaging_studies tables', function () {
    expect(Schema::connection('tenant')->hasTable('lab_tests'))->toBeTrue()
        ->and(Schema::connection('tenant')->hasTable('imaging_studies'))->toBeTrue()
        ->and(Schema::connection('tenant')->hasTable('lab_orders'))->toBeTrue()
        ->and(Schema::connection('tenant')->hasTable('imaging_orders'))->toBeTrue()
        ->and(Schema::connection('tenant')->hasTable('lab_order_items'))->toBeTrue()
        ->and(Schema::connection('tenant')->hasTable('imaging_order_items'))->toBeTrue()
        ->and(Schema::connection('tenant')->hasTable('imaging_reports'))->toBeTrue()
        ->and(Schema::connection('tenant')->hasTable('investigations'))->toBeFalse()
        ->and(Schema::connection('tenant')->hasTable('investigation_orders'))->toBeFalse()
        ->and(Schema::connection('tenant')->hasTable('radiology_results'))->toBeFalse();
});

it('drops kind columns and keeps sample_type only on lab tests', function () {
    expect(Schema::connection('tenant')->hasColumn('lab_tests', 'kind'))->toBeFalse()
        ->and(Schema::connection('tenant')->hasColumn('lab_orders', 'kind'))->toBeFalse()
        ->and(Schema::connection('tenant')->hasColumn('lab_tests', 'sample_type'))->toBeTrue()
        ->and(Schema::connection('tenant')->hasColumn('imaging_studies', 'sample_type'))->toBeFalse();
});

it('drops leftover location columns from lab orders and order items', function () {
    expect(Schema::connection('tenant')->hasColumn('lab_orders', 'test_location'))->toBeFalse()
        ->and(Schema::connection('tenant')->hasColumn('lab_orders', 'location'))->toBeFalse()
        ->and(Schema::connection('tenant')->hasColumn('lab_order_items', 'test_location'))->toBeFalse()
        ->and(Schema::connection('tenant')->hasColumn('imaging_order_items', 'test_location'))->toBeFalse();
});

it('adds billing catalog foreign keys and splits gl accounts', function () {
    Account::updateOrCreate(
        ['code' => '4300'],
        ['name' => 'Investigation Revenue', 'type' => 'revenue', 'is_system' => true, 'is_active' => true]
    );
    (new LabImagingSchemaSplit)->run();

    expect(Schema::connection('tenant')->hasColumn('bill_items', 'lab_test_id'))->toBeTrue()
        ->and(Schema::connection('tenant')->hasColumn('bill_items', 'imaging_study_id'))->toBeTrue()
        ->and(Schema::connection('tenant')->hasColumn('bill_items', 'investigation_id'))->toBeFalse();

    expect(Account::query()->where('code', '4300')->value('name'))->toBe('Lab Revenue')
        ->and(Account::query()->where('code', '4310')->value('name'))->toBe('Imaging Revenue');
});

it('accepts rows in both catalog tables', function () {
    $labId = DB::connection('tenant')->table('lab_tests')->insertGetId([
        'code' => 'CBC-SPLIT',
        'name' => 'CBC Split',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 100,
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $imgId = DB::connection('tenant')->table('imaging_studies')->insertGetId([
        'code' => 'CXR-SPLIT',
        'name' => 'Chest X-Ray Split',
        'category' => 'x-ray',
        'price' => 500,
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::connection('tenant')->table('lab_tests')->where('id', $labId)->value('name'))->toBe('CBC Split')
        ->and(DB::connection('tenant')->table('imaging_studies')->where('id', $imgId)->value('name'))->toBe('Chest X-Ray Split');
});
