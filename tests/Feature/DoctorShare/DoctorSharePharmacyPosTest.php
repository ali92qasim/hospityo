<?php

use App\Http\Middleware\CheckModule;
use App\Http\Middleware\EnsureTenantActive;
use App\Http\Middleware\SetTenantTimezone;
use App\Models\Account;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorShareAllocation;
use App\Models\DoctorShareItem;
use App\Models\DoctorShareRate;
use App\Models\InventoryTransaction;
use App\Models\IpdCareTeam;
use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\Visit;
use App\Services\DoctorShareService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $tenant = new Tenant;
    $tenant->id = 1;
    $tenant->status = 'active';
    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    Account::create(['code' => '1100', 'name' => 'Cash in Hand', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '4400', 'name' => 'Pharmacy Revenue', 'type' => 'revenue', 'is_system' => true]);

    $this->user = User::create([
        'name' => 'Pharmacy Share User',
        'email' => 'pharmacy-share@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('dispense pharmacy', 'web');
    $this->user->givePermissionTo('dispense pharmacy');
    $this->withoutMiddleware([
        EnsureTenantActive::class,
        SetTenantTimezone::class,
        CheckModule::class,
    ]);
    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Pharmacy Share Patient',
        'gender' => 'female',
        'age' => 28,
        'phone' => '03005550001',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03005550002',
        'emergency_relation' => 'Spouse',
    ]);

    $department = Department::create([
        'name' => 'Pharmacy Share Medicine',
        'code' => 'PH-SHARE',
        'status' => 'active',
    ]);

    $makeDoctor = function (string $name, string $number, string $email) use ($department): Doctor {
        return Doctor::create([
            'name' => $name,
            'doctor_no' => $number,
            'specialization' => 'General',
            'qualification' => 'MBBS',
            'phone' => '03005550003',
            'email' => $email,
            'gender' => 'male',
            'experience_years' => 6,
            'consultation_fee' => 1000,
            'shift_start' => '09:00:00',
            'shift_end' => '17:00:00',
            'status' => 'active',
            'department_id' => $department->id,
        ]);
    };

    $this->doctor = $makeDoctor('Dr. Pharmacy Share', 'DOC-PH-SHARE-001', 'doctor-pharmacy-share@example.com');
    $this->primaryDoctor = $makeDoctor('Dr. IPD Pharmacy', 'DOC-PH-SHARE-002', 'ipd-pharmacy-share@example.com');

    $this->opdVisit = Visit::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'with_doctor',
    ]);

    $unit = Unit::create([
        'name' => 'Tablet',
        'abbreviation' => 'TAB',
        'conversion_factor' => 1,
        'type' => 'solid',
        'is_active' => true,
    ]);
    $category = MedicineCategory::create([
        'code' => 'PH-SHARE',
        'name' => 'Pharmacy Share Tablets',
        'is_active' => true,
    ]);
    $this->medicine = Medicine::create([
        'name' => 'Share Medicine',
        'sku' => 'PH-SHARE-MED-001',
        'category_id' => $category->id,
        'base_unit_id' => $unit->id,
        'dispensing_unit_id' => $unit->id,
        'manage_stock' => true,
        'status' => 'active',
        'selling_price' => 50,
    ]);
    $this->stockBatch = InventoryTransaction::create([
        'medicine_id' => $this->medicine->id,
        'type' => 'stock_in',
        'quantity' => 100,
        'remaining_quantity' => 100,
        'unit_cost' => 30,
        'total_cost' => 3000,
        'batch_no' => 'PH-SHARE-BATCH-001',
        'expiry_date' => now()->addYear(),
        'created_by' => $this->user->id,
    ]);

    DoctorShareRate::create([
        'doctor_id' => $this->doctor->id,
        'service_category' => 'pharmacy',
        'percentage' => 15,
    ]);

    $this->createPrescription = function (Visit $visit, Doctor $doctor): Prescription {
        $prescription = Prescription::create([
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $doctor->id,
            'fulfillment_type' => 'in_house',
            'status' => 'pending',
            'prescribed_date' => now(),
            'total_amount' => 100,
        ]);
        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id' => $this->medicine->id,
            'quantity' => 2,
            'unit_price' => 50,
            'total_price' => 100,
        ]);

        return $prescription;
    };
});

it('does not create share items for walk-in checkout without a visit', function () {
    [$historicalBill, $historicalItem] = createHistoricalPharmacyShareBill(
        $this->patient,
        $this->opdVisit,
        $this->doctor,
        $this->user,
    );
    DoctorShareItem::create([
        'bill_id' => $historicalBill->id,
        'bill_item_id' => $historicalItem->id,
        'doctor_id' => $this->doctor->id,
        'rule_id' => null,
        'rule_snapshot' => ['legacy' => true],
        'base_amount' => 100,
        'share_amount' => 99,
        'status' => 'pending',
    ]);
    $before = fingerprintDoctorShareHistory();

    $this->post(route('pharmacy.pos.checkout'), [
        'mode' => 'walk_in',
        'patient_id' => $this->patient->id,
        'items' => [
            ['medicine_id' => $this->medicine->id, 'quantity' => 2, 'unit_price' => 50],
        ],
        'payment_amount' => 100,
        'payment_method' => 'cash',
    ])->assertRedirect();

    expect(DoctorShareItem::count())->toBe(1)
        ->and(fingerprintDoctorShareHistory())->toBe($before);
});

it('shares and allocates an OPD prescription checkout using the visit doctor', function () {
    $prescription = ($this->createPrescription)($this->opdVisit, $this->primaryDoctor);

    $this->post(route('pharmacy.pos.checkout'), [
        'mode' => 'prescription',
        'prescription_id' => $prescription->id,
        'patient_id' => $this->patient->id,
        'payment_amount' => 100,
        'payment_method' => 'cash',
    ])->assertRedirect();

    $bill = Bill::where('prescription_id', $prescription->id)->firstOrFail();
    $share = DoctorShareItem::where('bill_id', $bill->id)->firstOrFail();

    expect($share->doctor_id)->toBe($this->doctor->id)
        ->and((float) $share->share_amount)->toBe(15.0)
        ->and($share->rule_snapshot)->toBe([
            'doctor_id' => $this->doctor->id,
            'service_category' => 'pharmacy',
            'percentage' => '15.00',
            'source' => 'category',
        ])
        ->and(DoctorShareAllocation::where('doctor_share_item_id', $share->id)->count())->toBe(1)
        ->and((float) DoctorShareAllocation::where('doctor_share_item_id', $share->id)->value('amount'))->toBe(15.0);
});

it('attributes an IPD prescription checkout to the primary care-team doctor', function () {
    $ipdVisit = Visit::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => null,
        'visit_type' => 'ipd',
        'visit_datetime' => now(),
        'status' => 'admitted',
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
        'service_category' => 'pharmacy',
        'percentage' => 15,
    ]);
    $prescription = ($this->createPrescription)($ipdVisit, $this->doctor);

    $this->post(route('pharmacy.pos.checkout'), [
        'mode' => 'prescription',
        'prescription_id' => $prescription->id,
        'patient_id' => $this->patient->id,
        'payment_amount' => 100,
        'payment_method' => 'cash',
    ])->assertRedirect();

    $bill = Bill::where('prescription_id', $prescription->id)->firstOrFail();
    $share = DoctorShareItem::where('bill_id', $bill->id)->firstOrFail();

    expect($share->doctor_id)->toBe($this->primaryDoctor->id)
        ->and((float) $share->share_amount)->toBe(15.0);
});

it('uses line categories when calculating mixed OPD bill shares', function () {
    DoctorShareRate::create([
        'doctor_id' => $this->doctor->id,
        'service_category' => 'opd',
        'percentage' => 20,
    ]);
    $bill = Bill::create([
        'patient_id' => $this->patient->id,
        'visit_id' => $this->opdVisit->id,
        'bill_number' => 'BILL-PH-SHARE-MIXED',
        'bill_date' => now(),
        'bill_type' => 'opd',
        'subtotal' => 200,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 200,
        'paid_amount' => 0,
        'due_amount' => 200,
        'status' => 'pending',
        'created_by' => $this->user->id,
    ]);
    $pharmacyItem = BillItem::create([
        'bill_id' => $bill->id,
        'medicine_id' => $this->medicine->id,
        'item_category' => 'pharmacy',
        'description' => 'Pharmacy line',
        'quantity' => 1,
        'unit_price' => 100,
    ]);
    $opdItem = BillItem::create([
        'bill_id' => $bill->id,
        'item_category' => 'opd',
        'description' => 'OPD line',
        'quantity' => 1,
        'unit_price' => 100,
    ]);

    DoctorShareService::calculate($bill);

    expect((float) DoctorShareItem::where('bill_item_id', $pharmacyItem->id)->value('share_amount'))->toBe(15.0)
        ->and((float) DoctorShareItem::where('bill_item_id', $opdItem->id)->value('share_amount'))->toBe(20.0);
});

it('does not rewrite historical share rows during prescription checkout', function () {
    [$historicalBill, $historicalItem] = createHistoricalPharmacyShareBill(
        $this->patient,
        $this->opdVisit,
        $this->doctor,
        $this->user,
    );
    $historicalShare = DoctorShareItem::create([
        'bill_id' => $historicalBill->id,
        'bill_item_id' => $historicalItem->id,
        'doctor_id' => $this->doctor->id,
        'rule_id' => null,
        'rule_snapshot' => ['legacy' => true, 'percentage' => '99.00'],
        'base_amount' => 100,
        'share_amount' => 99,
        'status' => 'pending',
    ]);
    $before = fingerprintDoctorShareHistory();
    $oldValues = $historicalShare->only(['rule_snapshot', 'share_amount', 'rule_id', 'doctor_id']);
    $prescription = ($this->createPrescription)($this->opdVisit, $this->doctor);

    $this->post(route('pharmacy.pos.checkout'), [
        'mode' => 'prescription',
        'prescription_id' => $prescription->id,
        'patient_id' => $this->patient->id,
        'payment_amount' => 100,
        'payment_method' => 'cash',
    ])->assertRedirect();

    $newBill = Bill::where('prescription_id', $prescription->id)->firstOrFail();
    expect(DoctorShareItem::where('bill_id', $newBill->id)->count())->toBe(1);

    DB::connection('tenant')->table('doctor_share_allocations')->where('bill_id', $newBill->id)->delete();
    DB::connection('tenant')->table('doctor_share_items')->where('bill_id', $newBill->id)->delete();
    $historicalShare->refresh();

    expect(fingerprintDoctorShareHistory())->toBe($before)
        ->and($historicalShare->only(['rule_snapshot', 'share_amount', 'rule_id', 'doctor_id']))->toBe($oldValues);
});

function createHistoricalPharmacyShareBill(
    Patient $patient,
    Visit $visit,
    Doctor $doctor,
    User $user,
): array {
    $bill = Bill::create([
        'patient_id' => $patient->id,
        'visit_id' => $visit->id,
        'bill_number' => 'BILL-PH-SHARE-HISTORY-'.uniqid(),
        'bill_date' => now(),
        'bill_type' => 'opd',
        'subtotal' => 100,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 100,
        'paid_amount' => 0,
        'due_amount' => 100,
        'status' => 'pending',
        'created_by' => $user->id,
    ]);
    $item = BillItem::create([
        'bill_id' => $bill->id,
        'item_category' => 'opd',
        'description' => "Historical {$doctor->doctor_no}",
        'quantity' => 1,
        'unit_price' => 100,
    ]);

    return [$bill, $item];
}
