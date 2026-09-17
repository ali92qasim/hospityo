<?php

use App\Models\Account;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorShareItem;
use App\Models\DoctorShareRate;
use App\Models\ImagingStudy;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use App\Services\AccountingService;
use App\Services\BillItemCategoryResolver;
use App\Services\DoctorShareService;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'billing-test@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Account::create(['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '4100', 'name' => 'OPD Revenue', 'type' => 'revenue', 'is_system' => true]);
    Account::updateOrCreate(
        ['code' => '4300'],
        ['name' => 'Lab Revenue', 'type' => 'revenue', 'is_system' => true, 'is_active' => true]
    );
    Account::updateOrCreate(
        ['code' => '4310'],
        ['name' => 'Imaging Revenue', 'type' => 'revenue', 'is_system' => true, 'is_active' => true]
    );

    $this->patient = Patient::create([
        'name' => 'Jane Patient',
        'gender' => 'female',
        'age' => 30,
        'phone' => '03001112222',
        'emergency_name' => 'John Patient',
        'emergency_phone' => '03003334444',
        'emergency_relation' => 'Spouse',
    ]);

    $this->department = Department::create([
        'name' => 'General Medicine',
        'code' => 'GM',
        'status' => 'active',
    ]);

    $this->doctor = Doctor::create([
        'user_id' => $this->user->id,
        'name' => 'Dr. Share Test',
        'doctor_no' => 'DOC-001',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005556666',
        'email' => 'doctor@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $this->department->id,
    ]);

    $this->visit = Visit::create([
        'visit_no' => 'VIS-001',
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'department_id' => $this->department->id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now(),
    ]);

    $this->service = Service::create([
        'name' => 'Consultation',
        'code' => 'CONS-001',
        'category' => 'consultation',
        'price' => 1000,
        'is_active' => true,
    ]);

    $this->labTest = LabTest::create([
        'code' => 'CBC-001',
        'name' => 'CBC',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 500,
        'turnaround_time' => '24',
        'is_active' => true,
    ]);

    $this->imagingStudy = ImagingStudy::create([
        'code' => 'CXR-001',
        'name' => 'Chest X-Ray',
        'category' => 'x-ray',
        'price' => 1500,
        'is_active' => true,
    ]);
});

it('resolves item categories from line content and bill context', function () {
    expect(BillItemCategoryResolver::resolve(['lab_test_id' => 1], 'opd'))
        ->toBe('lab');

    expect(BillItemCategoryResolver::resolve(['imaging_study_id' => 1], 'opd'))
        ->toBe('imaging');

    expect(BillItemCategoryResolver::resolve(['service_id' => $this->service->id], 'opd'))
        ->toBe('opd');

    expect(BillItemCategoryResolver::resolve(['service_id' => $this->service->id], 'ipd'))
        ->toBe('ipd');
});

it('posts split revenue accounts for mixed OPD and lab items', function () {
    $bill = Bill::create([
        'patient_id' => $this->patient->id,
        'visit_id' => $this->visit->id,
        'bill_number' => 'BILL-MIXED-001',
        'bill_date' => now(),
        'bill_type' => 'opd',
        'subtotal' => 1500,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 1500,
        'paid_amount' => 0,
        'due_amount' => 1500,
        'status' => 'pending',
        'created_by' => $this->user->id,
    ]);

    BillItem::create([
        'bill_id' => $bill->id,
        'service_id' => $this->service->id,
        'item_category' => 'opd',
        'description' => 'Consultation',
        'quantity' => 1,
        'unit_price' => 1000,
        'total_price' => 1000,
    ]);

    BillItem::create([
        'bill_id' => $bill->id,
        'lab_test_id' => $this->labTest->id,
        'item_category' => 'lab',
        'description' => 'CBC',
        'quantity' => 1,
        'unit_price' => 500,
        'total_price' => 500,
    ]);

    $entry = AccountingService::postBillEntry($bill);

    expect($entry)->not->toBeNull();

    $revenueLines = $entry->lines()
        ->where('credit', '>', 0)
        ->get()
        ->map(fn ($line) => [
            'code' => Account::find($line->account_id)->code,
            'credit' => (float) $line->credit,
        ])
        ->sortBy('code')
        ->values()
        ->all();

    expect($revenueLines)->toBe([
        ['code' => '4100', 'credit' => 1000.0],
        ['code' => '4300', 'credit' => 500.0],
    ]);
});

it('credits imaging revenue to account 4310', function () {
    $bill = Bill::create([
        'patient_id' => $this->patient->id,
        'visit_id' => $this->visit->id,
        'bill_number' => 'BILL-IMG-001',
        'bill_date' => now(),
        'bill_type' => 'opd',
        'subtotal' => 1500,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 1500,
        'paid_amount' => 0,
        'due_amount' => 1500,
        'status' => 'pending',
        'created_by' => $this->user->id,
    ]);

    BillItem::create([
        'bill_id' => $bill->id,
        'imaging_study_id' => $this->imagingStudy->id,
        'item_category' => 'imaging',
        'description' => 'Chest X-Ray',
        'quantity' => 1,
        'unit_price' => 1500,
        'total_price' => 1500,
    ]);

    $entry = AccountingService::postBillEntry($bill);

    expect($entry)->not->toBeNull();

    $revenueLines = $entry->lines()
        ->where('credit', '>', 0)
        ->get()
        ->map(fn ($line) => [
            'code' => Account::find($line->account_id)->code,
            'credit' => (float) $line->credit,
        ])
        ->values()
        ->all();

    expect($revenueLines)->toBe([
        ['code' => '4310', 'credit' => 1500.0],
    ]);
});

it('applies category share rates to mixed OPD and lab lines', function () {
    DoctorShareRate::create([
        'doctor_id' => $this->doctor->id,
        'service_category' => 'opd',
        'percentage' => 20,
    ]);

    DoctorShareRate::create([
        'doctor_id' => $this->doctor->id,
        'service_category' => 'lab',
        'percentage' => 30,
    ]);

    $bill = Bill::create([
        'patient_id' => $this->patient->id,
        'visit_id' => $this->visit->id,
        'bill_number' => 'BILL-SHARE-001',
        'bill_date' => now(),
        'bill_type' => 'opd',
        'subtotal' => 1500,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 1500,
        'paid_amount' => 0,
        'due_amount' => 1500,
        'status' => 'pending',
        'created_by' => $this->user->id,
    ]);

    $serviceItem = BillItem::create([
        'bill_id' => $bill->id,
        'service_id' => $this->service->id,
        'item_category' => 'opd',
        'description' => 'Consultation',
        'quantity' => 1,
        'unit_price' => 1000,
        'total_price' => 1000,
    ]);

    $labItem = BillItem::create([
        'bill_id' => $bill->id,
        'lab_test_id' => $this->labTest->id,
        'item_category' => 'lab',
        'description' => 'CBC',
        'quantity' => 1,
        'unit_price' => 500,
        'total_price' => 500,
    ]);

    DoctorShareService::calculate($bill);

    $serviceShare = DoctorShareItem::where('bill_item_id', $serviceItem->id)->first();
    $labShare = DoctorShareItem::where('bill_item_id', $labItem->id)->first();

    expect($serviceShare)->not->toBeNull()
        ->and((float) $serviceShare->share_amount)->toBe(200.0)
        ->and($labShare)->not->toBeNull()
        ->and((float) $labShare->share_amount)->toBe(150.0);
});

it('applies lab and imaging share rates to matching bill lines', function () {
    DoctorShareRate::create([
        'doctor_id' => $this->doctor->id,
        'service_category' => 'lab',
        'percentage' => 30,
    ]);

    DoctorShareRate::create([
        'doctor_id' => $this->doctor->id,
        'service_category' => 'imaging',
        'percentage' => 40,
    ]);

    $bill = Bill::create([
        'patient_id' => $this->patient->id,
        'visit_id' => $this->visit->id,
        'bill_number' => 'BILL-SHARE-BOTH-001',
        'bill_date' => now(),
        'bill_type' => 'opd',
        'subtotal' => 2000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 2000,
        'paid_amount' => 0,
        'due_amount' => 2000,
        'status' => 'pending',
        'created_by' => $this->user->id,
    ]);

    $labItem = BillItem::create([
        'bill_id' => $bill->id,
        'lab_test_id' => $this->labTest->id,
        'item_category' => 'lab',
        'description' => 'CBC',
        'quantity' => 1,
        'unit_price' => 500,
        'total_price' => 500,
    ]);

    $imagingItem = BillItem::create([
        'bill_id' => $bill->id,
        'imaging_study_id' => $this->imagingStudy->id,
        'item_category' => 'imaging',
        'description' => 'Chest X-Ray',
        'quantity' => 1,
        'unit_price' => 1500,
        'total_price' => 1500,
    ]);

    DoctorShareService::calculate($bill);

    $labShare = DoctorShareItem::where('bill_item_id', $labItem->id)->first();
    $imagingShare = DoctorShareItem::where('bill_item_id', $imagingItem->id)->first();

    expect($labShare)->not->toBeNull()
        ->and((float) $labShare->share_amount)->toBe(150.0)
        ->and($imagingShare)->not->toBeNull()
        ->and((float) $imagingShare->share_amount)->toBe(600.0);
});

it('applies the lab share rate on visit bills', function () {
    DoctorShareRate::create([
        'doctor_id' => $this->doctor->id,
        'service_category' => 'lab',
        'percentage' => 25,
    ]);

    $bill = Bill::create([
        'patient_id' => $this->patient->id,
        'visit_id' => $this->visit->id,
        'bill_number' => 'BILL-LAB-SCOPE-001',
        'bill_date' => now(),
        'bill_type' => 'opd',
        'subtotal' => 500,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 500,
        'paid_amount' => 0,
        'due_amount' => 500,
        'status' => 'pending',
        'created_by' => $this->user->id,
    ]);

    $labItem = BillItem::create([
        'bill_id' => $bill->id,
        'lab_test_id' => $this->labTest->id,
        'item_category' => 'lab',
        'description' => 'CBC',
        'quantity' => 1,
        'unit_price' => 500,
        'total_price' => 500,
    ]);

    DoctorShareService::calculate($bill);

    $labShare = DoctorShareItem::where('bill_item_id', $labItem->id)->first();

    expect($labShare)->not->toBeNull()
        ->and((float) $labShare->share_amount)->toBe(125.0);
});
