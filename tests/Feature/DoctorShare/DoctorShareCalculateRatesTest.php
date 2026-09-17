<?php

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorShareAllocation;
use App\Models\DoctorShareItem;
use App\Models\DoctorShareRate;
use App\Models\DoctorShareRule;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use App\Services\DoctorShareService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Rate Calculation User',
        'email' => 'rate-calculation@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->department = Department::create([
        'name' => 'General Medicine',
        'code' => 'GM-RATE',
        'status' => 'active',
    ]);

    $this->doctor = Doctor::create([
        'user_id' => $this->user->id,
        'name' => 'Dr. Rate Test',
        'doctor_no' => 'DOC-RATE-001',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005556666',
        'email' => 'rate-doctor@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $this->department->id,
    ]);

    $this->patient = Patient::create([
        'name' => 'Rate Patient',
        'gender' => 'female',
        'age' => 30,
        'phone' => '03001112222',
        'emergency_name' => 'Rate Contact',
        'emergency_phone' => '03003334444',
        'emergency_relation' => 'Spouse',
    ]);

    $this->visit = Visit::create([
        'visit_no' => 'VIS-RATE-001',
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'department_id' => $this->department->id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now(),
    ]);

    $this->service = Service::create([
        'name' => 'Consultation',
        'code' => 'CONS-RATE-001',
        'category' => 'consultation',
        'price' => 1000,
        'is_active' => true,
    ]);

    $this->labTest = LabTest::create([
        'code' => 'CBC-RATE-001',
        'name' => 'CBC',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 500,
        'turnaround_time' => '24',
        'is_active' => true,
    ]);

    $this->createBill = function (string $number, array $items): array {
        $total = array_sum(array_column($items, 'total_price'));

        $bill = Bill::create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'bill_number' => $number,
            'bill_date' => now(),
            'bill_type' => 'opd',
            'subtotal' => $total,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $total,
            'paid_amount' => 0,
            'due_amount' => $total,
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        return [
            $bill,
            collect($items)->map(fn (array $item) => BillItem::create([
                'bill_id' => $bill->id,
                ...$item,
            ])),
        ];
    };
});

it('calculates mixed bill shares from category rates without rules', function () {
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

    [$bill, $items] = ($this->createBill)('BILL-RATES-MIXED', [
        [
            'service_id' => $this->service->id,
            'item_category' => 'opd',
            'description' => 'Consultation',
            'quantity' => 1,
            'unit_price' => 1000,
            'total_price' => 1000,
        ],
        [
            'lab_test_id' => $this->labTest->id,
            'item_category' => 'lab',
            'description' => 'CBC',
            'quantity' => 1,
            'unit_price' => 500,
            'total_price' => 500,
        ],
    ]);

    DoctorShareService::calculate($bill);

    $shares = DoctorShareItem::query()
        ->whereIn('bill_item_id', $items->pluck('id'))
        ->orderBy('bill_item_id')
        ->get();

    expect(DoctorShareRule::query()->count())->toBe(0)
        ->and($shares)->toHaveCount(2)
        ->and((float) $shares[0]->share_amount)->toBe(200.0)
        ->and((float) $shares[1]->share_amount)->toBe(150.0)
        ->and($shares[0]->rule_id)->toBeNull()
        ->and($shares[1]->rule_id)->toBeNull();
});

it('falls back to the general rate when the category rate is missing', function () {
    DoctorShareRate::create([
        'doctor_id' => $this->doctor->id,
        'service_category' => 'general',
        'percentage' => 10,
    ]);

    [$bill, $items] = ($this->createBill)('BILL-RATES-GENERAL', [[
        'service_id' => $this->service->id,
        'item_category' => 'opd',
        'description' => 'Consultation',
        'quantity' => 1,
        'unit_price' => 1000,
        'total_price' => 1000,
    ]]);

    DoctorShareService::calculate($bill);

    $share = DoctorShareItem::where('bill_item_id', $items->first()->id)->first();

    expect($share)->not->toBeNull()
        ->and((float) $share->share_amount)->toBe(100.0)
        ->and($share->rule_snapshot['source'])->toBe('general');
});

it('does not rewrite existing items when calculating a different bill', function () {
    $archivedRule = DoctorShareRule::create([
        'doctor_id' => $this->doctor->id,
        'share_type' => 'percentage',
        'share_value' => 70,
        'applies_to' => 'opd',
        'is_active' => false,
        'created_by' => $this->user->id,
    ]);

    [$historicalBill, $historicalItems] = ($this->createBill)('BILL-RATES-HISTORY', [[
        'service_id' => $this->service->id,
        'item_category' => 'opd',
        'description' => 'Historical consultation',
        'quantity' => 1,
        'unit_price' => 1000,
        'total_price' => 1000,
    ]]);

    $historicalShare = DoctorShareItem::create([
        'bill_id' => $historicalBill->id,
        'bill_item_id' => $historicalItems->first()->id,
        'doctor_id' => $this->doctor->id,
        'rule_id' => $archivedRule->id,
        'rule_snapshot' => [
            'rule_id' => $archivedRule->id,
            'share_type' => 'percentage',
            'share_value' => '70.00',
            'applies_to' => 'opd',
        ],
        'base_amount' => 1000,
        'share_amount' => 700,
        'status' => 'pending',
    ]);
    DoctorShareAllocation::create([
        'doctor_share_item_id' => $historicalShare->id,
        'payment_id' => null,
        'bill_id' => $historicalBill->id,
        'doctor_id' => $this->doctor->id,
        'amount' => 700,
        'type' => 'collection',
    ]);

    DoctorShareRate::create([
        'doctor_id' => $this->doctor->id,
        'service_category' => 'opd',
        'percentage' => 20,
    ]);
    [$newBill] = ($this->createBill)('BILL-RATES-NEW', [[
        'service_id' => $this->service->id,
        'item_category' => 'opd',
        'description' => 'New consultation',
        'quantity' => 1,
        'unit_price' => 1000,
        'total_price' => 1000,
    ]]);

    $before = fingerprintDoctorShareHistory();
    $oldValues = $historicalShare->only(['rule_snapshot', 'share_amount', 'rule_id']);

    DoctorShareService::calculate($newBill);

    expect(DoctorShareItem::where('bill_id', $newBill->id)->count())->toBe(1);

    DB::connection('tenant')->table('doctor_share_allocations')
        ->where('bill_id', $newBill->id)
        ->delete();
    DB::connection('tenant')->table('doctor_share_items')
        ->where('bill_id', $newBill->id)
        ->delete();

    $historicalShare->refresh();

    expect(fingerprintDoctorShareHistory())->toBe($before)
        ->and($historicalShare->only(['rule_snapshot', 'share_amount', 'rule_id']))->toBe($oldValues);
});

it('stores the new rate snapshot shape', function () {
    DoctorShareRate::create([
        'doctor_id' => $this->doctor->id,
        'service_category' => 'lab',
        'percentage' => 30,
    ]);

    [$bill, $items] = ($this->createBill)('BILL-RATES-SNAPSHOT', [[
        'lab_test_id' => $this->labTest->id,
        'item_category' => 'lab',
        'description' => 'CBC',
        'quantity' => 1,
        'unit_price' => 500,
        'total_price' => 500,
    ]]);

    DoctorShareService::calculate($bill);

    $snapshot = DoctorShareItem::where('bill_item_id', $items->first()->id)
        ->firstOrFail()
        ->rule_snapshot;

    expect($snapshot)->toBe([
        'doctor_id' => $this->doctor->id,
        'service_category' => 'lab',
        'percentage' => '30.00',
        'source' => 'category',
    ])->not->toHaveKey('applies_to');
});
