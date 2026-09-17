<?php

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorShareItem;
use App\Models\DoctorShareRate;
use App\Models\DoctorShareRule;
use App\Models\IpdCareTeam;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\DoctorShareService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'IPD Share User',
        'email' => 'ipd-share@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $department = Department::create([
        'name' => 'IPD Share Medicine',
        'code' => 'IPD-SHARE',
        'status' => 'active',
    ]);

    $makeDoctor = function (string $name, string $number, string $email) use ($department): Doctor {
        return Doctor::create([
            'name' => $name,
            'doctor_no' => $number,
            'specialization' => 'General',
            'qualification' => 'MBBS',
            'phone' => '03005556666',
            'email' => $email,
            'gender' => 'male',
            'experience_years' => 5,
            'consultation_fee' => 1000,
            'shift_start' => '09:00:00',
            'shift_end' => '17:00:00',
            'status' => 'active',
            'department_id' => $department->id,
        ]);
    };

    $this->primaryDoctor = $makeDoctor('Dr. IPD Primary', 'DOC-IPD-001', 'ipd-primary@example.com');
    $this->spineDoctor = $makeDoctor('Dr. OPD Spine', 'DOC-OPD-001', 'opd-spine@example.com');
    $this->careTeamDoctor = $makeDoctor('Dr. Ignored Care Team', 'DOC-IPD-002', 'ignored-care-team@example.com');

    $this->patient = Patient::create([
        'name' => 'IPD Share Patient',
        'gender' => 'female',
        'age' => 30,
        'phone' => '03001112222',
        'emergency_name' => 'IPD Contact',
        'emergency_phone' => '03003334444',
        'emergency_relation' => 'Spouse',
    ]);

    $this->createBill = function (
        Visit $visit,
        string $number,
        string $billType,
        string $itemCategory,
        int $unitPrice
    ): array {
        $bill = Bill::create([
            'patient_id' => $this->patient->id,
            'visit_id' => $visit->id,
            'bill_number' => $number,
            'bill_date' => now(),
            'bill_type' => $billType,
            'subtotal' => $unitPrice,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $unitPrice,
            'paid_amount' => 0,
            'due_amount' => $unitPrice,
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        $item = BillItem::create([
            'bill_id' => $bill->id,
            'item_category' => $itemCategory,
            'description' => "{$billType} line",
            'quantity' => 1,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice,
        ]);

        return [$bill, $item];
    };
});

it('attributes an IPD bill to the active primary care-team doctor', function () {
    $visit = Visit::create([
        'visit_no' => 'VIS-IPD-SHARE-001',
        'patient_id' => $this->patient->id,
        'doctor_id' => null,
        'visit_type' => 'ipd',
        'status' => 'admitted',
        'visit_datetime' => now(),
    ]);
    IpdCareTeam::create([
        'visit_id' => $visit->id,
        'doctor_id' => $this->primaryDoctor->id,
        'is_primary' => true,
        'added_at' => now(),
        'added_by' => $this->user->id,
    ]);
    DoctorShareRate::create([
        'doctor_id' => $this->primaryDoctor->id,
        'service_category' => 'ipd',
        'percentage' => 40,
    ]);
    [$bill, $item] = ($this->createBill)($visit, 'BILL-IPD-SHARE-001', 'ipd', 'ipd', 1000);

    DoctorShareService::calculate($bill);

    $share = DoctorShareItem::where('bill_item_id', $item->id)->first();

    expect($share)->not->toBeNull()
        ->and($share->doctor_id)->toBe($this->primaryDoctor->id)
        ->and((float) $share->share_amount)->toBe(400.0);
});

it('keeps attributing OPD bills to the visit doctor and ignores care team', function () {
    $visit = Visit::create([
        'visit_no' => 'VIS-OPD-SHARE-001',
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->spineDoctor->id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now(),
    ]);
    IpdCareTeam::create([
        'visit_id' => $visit->id,
        'doctor_id' => $this->careTeamDoctor->id,
        'is_primary' => true,
        'added_at' => now(),
        'added_by' => $this->user->id,
    ]);
    DoctorShareRate::create([
        'doctor_id' => $this->spineDoctor->id,
        'service_category' => 'opd',
        'percentage' => 25,
    ]);
    DoctorShareRate::create([
        'doctor_id' => $this->careTeamDoctor->id,
        'service_category' => 'opd',
        'percentage' => 90,
    ]);
    [$bill, $item] = ($this->createBill)($visit, 'BILL-OPD-SHARE-001', 'opd', 'opd', 1000);

    DoctorShareService::calculate($bill);

    $share = DoctorShareItem::where('bill_item_id', $item->id)->firstOrFail();

    expect($share->doctor_id)->toBe($this->spineDoctor->id)
        ->and((float) $share->share_amount)->toBe(250.0);
});

it('leaves freeze fields on existing items untouched when calculating another bill', function () {
    $opdVisit = Visit::create([
        'visit_no' => 'VIS-IPD-HISTORY-001',
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->spineDoctor->id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now(),
    ]);
    [$historicalBill, $historicalBillItem] = ($this->createBill)(
        $opdVisit,
        'BILL-IPD-HISTORY-001',
        'opd',
        'opd',
        1000
    );
    $archivedRule = DoctorShareRule::create([
        'doctor_id' => $this->spineDoctor->id,
        'share_type' => 'percentage',
        'share_value' => 70,
        'applies_to' => 'opd',
        'is_active' => false,
        'created_by' => $this->user->id,
    ]);
    $historicalShare = DoctorShareItem::create([
        'bill_id' => $historicalBill->id,
        'bill_item_id' => $historicalBillItem->id,
        'doctor_id' => $this->spineDoctor->id,
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

    $ipdVisit = Visit::create([
        'visit_no' => 'VIS-IPD-HISTORY-002',
        'patient_id' => $this->patient->id,
        'doctor_id' => null,
        'visit_type' => 'ipd',
        'status' => 'admitted',
        'visit_datetime' => now(),
    ]);
    IpdCareTeam::create([
        'visit_id' => $ipdVisit->id,
        'doctor_id' => $this->primaryDoctor->id,
        'is_primary' => true,
        'added_at' => now(),
        'added_by' => $this->user->id,
    ]);
    DoctorShareRate::create([
        'doctor_id' => $this->primaryDoctor->id,
        'service_category' => 'ipd',
        'percentage' => 40,
    ]);
    [$ipdBill] = ($this->createBill)($ipdVisit, 'BILL-IPD-HISTORY-002', 'ipd', 'ipd', 1000);
    $before = fingerprintDoctorShareHistory();
    $oldValues = $historicalShare->only(['rule_snapshot', 'share_amount', 'rule_id', 'doctor_id']);

    DoctorShareService::calculate($ipdBill);

    expect(DoctorShareItem::where('bill_id', $ipdBill->id)->count())->toBe(1);

    DB::connection('tenant')->table('doctor_share_items')
        ->where('bill_id', $ipdBill->id)
        ->delete();
    $historicalShare->refresh();

    expect(fingerprintDoctorShareHistory())->toBe($before)
        ->and($historicalShare->only(['rule_snapshot', 'share_amount', 'rule_id', 'doctor_id']))->toBe($oldValues);
});

it('stores the rate snapshot shape and null rule id for new IPD items', function () {
    $visit = Visit::create([
        'visit_no' => 'VIS-IPD-SNAPSHOT-001',
        'patient_id' => $this->patient->id,
        'doctor_id' => null,
        'visit_type' => 'ipd',
        'status' => 'admitted',
        'visit_datetime' => now(),
    ]);
    IpdCareTeam::create([
        'visit_id' => $visit->id,
        'doctor_id' => $this->primaryDoctor->id,
        'is_primary' => true,
        'added_at' => now(),
        'added_by' => $this->user->id,
    ]);
    DoctorShareRate::create([
        'doctor_id' => $this->primaryDoctor->id,
        'service_category' => 'ipd',
        'percentage' => 40,
    ]);
    [$bill, $item] = ($this->createBill)($visit, 'BILL-IPD-SNAPSHOT-001', 'ipd', 'ipd', 750);

    DoctorShareService::calculate($bill);

    $share = DoctorShareItem::where('bill_item_id', $item->id)->firstOrFail();

    expect($share->rule_snapshot)->toBe([
        'doctor_id' => $this->primaryDoctor->id,
        'service_category' => 'ipd',
        'percentage' => '40.00',
        'source' => 'category',
    ])->and($share->rule_id)->toBeNull()
        ->and((float) $share->share_amount)->toBe(300.0);
});
