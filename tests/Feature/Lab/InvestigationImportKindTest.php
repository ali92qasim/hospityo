<?php

use App\Models\ImagingStudy;
use App\Models\LabTest;
use App\Services\InvestigationImportService;

it('imports lab rows with parameters', function () {
    $path = storage_path('framework/testing-lab-import.csv');
    file_put_contents($path, <<<'CSV'
code,name,category,sample_type,price,turnaround_time,description,instructions,param_1_name,param_1_unit,param_1_reference_range
IMP-CBC,Imported CBC,hematology,blood,500,2 hours,desc,instr,Hemoglobin,g/dL,12-17
CSV);

    $result = app(InvestigationImportService::class)->importFromFile($path, 'lab');

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->toBeEmpty();

    $labTest = LabTest::where('code', 'IMP-CBC')->first();
    expect($labTest)->not->toBeNull()
        ->and($labTest->parameters)->toHaveCount(1);

    @unlink($path);
});

it('rejects imaging import rows that include parameters', function () {
    $path = storage_path('framework/testing-imaging-import.csv');
    file_put_contents($path, <<<'CSV'
code,name,category,price,turnaround_time,description,instructions,param_1_name,param_1_unit,param_1_reference_range
IMP-XRAY,Chest XRay,x-ray,1000,1 day,desc,instr,ShouldNotExist,mm,1-2
CSV);

    $result = app(InvestigationImportService::class)->importFromFile($path, 'imaging');

    expect($result['created'])->toBe(0)
        ->and($result['errors'][0])->toContain('cannot include lab parameters');

    @unlink($path);
});

it('rejects importing a lab code as an imaging study', function () {
    LabTest::create([
        'code' => 'IMP-FLIP',
        'name' => 'Lab Flip',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 100,
        'is_active' => true,
    ]);

    $path = storage_path('framework/testing-flip-import.csv');
    file_put_contents($path, <<<'CSV'
code,name,category,price,turnaround_time,description,instructions
IMP-FLIP,Now Imaging,x-ray,1000,1 day,desc,instr
CSV);

    $result = app(InvestigationImportService::class)->importFromFile($path, 'imaging');

    expect($result['updated'])->toBe(0)
        ->and($result['errors'][0])->toContain('cannot be imported');

    @unlink($path);
});

it('imports imaging studies without a kind column or parameters', function () {
    $path = storage_path('framework/testing-imaging-ok-import.csv');
    file_put_contents($path, <<<'CSV'
code,name,category,price,turnaround_time,description,instructions
IMP-US,Imported Ultrasound,ultrasound,1800,1 day,desc,instr
CSV);

    $result = app(InvestigationImportService::class)->importFromFile($path, 'imaging');

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->toBeEmpty();

    $study = ImagingStudy::where('code', 'IMP-US')->first();
    expect($study)->not->toBeNull()
        ->and($study->category)->toBe('ultrasound')
        ->and(\Illuminate\Support\Facades\Schema::connection('tenant')->hasColumn('imaging_studies', 'kind'))->toBeFalse();

    @unlink($path);
});
