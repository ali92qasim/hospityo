<?php

/**
 * End-to-end IPD billing integration tests.
 *
 * Accounting rules under test:
 * - Bill journal entries (reference_type Bill) are created ONLY when the draft is finalized at discharge.
 * - Admission advance journal entries (reference_type AdmissionAdvance) are created when each advance is received.
 * - Advance application & refund journal entries are created at discharge finalization.
 */

use App\Models\Account;
use App\Models\AdmissionAdvance;
use App\Models\Bed;
use App\Models\Bill;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\JournalEntry;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use App\Http\Controllers\BillController;
use App\Services\AccountingService;
use App\Services\IpdDischargeBillingService;
use App\Services\IpdDraftBillService;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'IPD Integration Clerk',
        'email' => 'ipd-integration@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($this->user);

    foreach ([
        ['code' => '1100', 'name' => 'Cash in Hand', 'type' => 'asset'],
        ['code' => '1110', 'name' => 'Bank Account', 'type' => 'asset'],
        ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset'],
        ['code' => '2300', 'name' => 'Advance from Patients', 'type' => 'liability'],
        ['code' => '4200', 'name' => 'IPD Revenue', 'type' => 'revenue'],
    ] as $account) {
        Account::create([...$account, 'is_system' => true]);
    }

    $this->patient = Patient::create([
        'name' => 'Integration Patient',
        'gender' => 'male',
        'age' => 45,
        'phone' => '03007778888',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03006665555',
        'emergency_relation' => 'Brother',
    ]);

    $department = Department::create(['name' => 'Medicine', 'code' => 'MED-INT', 'status' => 'active']);

    $this->doctor = Doctor::create([
        'name' => 'Dr. Integration',
        'doctor_no' => 'DOC-INT',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03004443333',
        'email' => 'dr-int@example.com',
        'gender' => 'male',
        'experience_years' => 8,
        'consultation_fee' => 1500,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $ward = Ward::create([
        'name' => 'Integration Ward',
        'department_id' => $department->id,
        'capacity' => 8,
        'ward_type' => 'general',
        'status' => 'active',
    ]);

    $this->bed = Bed::create([
        'ward_id' => $ward->id,
        'bed_number' => 'IW-01',
        'bed_type' => 'general',
        'daily_rate' => 2000,
        'status' => 'occupied',
    ]);

    $this->visit = Visit::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'visit_type' => 'ipd',
        'status' => 'admitted',
        'visit_datetime' => now(),
    ]);

    $this->admission = $this->visit->admission()->create([
        'bed_id' => $this->bed->id,
        'admission_date' => now(),
        'status' => 'active',
    ]);

    $this->bill = IpdDraftBillService::ensureForVisit($this->visit);
});

function countJournalEntries(string $referenceType, ?int $referenceId = null): int
{
    $query = JournalEntry::query()
        ->where('reference_type', $referenceType)
        ->where('entry_type', 'original');

    if ($referenceId !== null) {
        $query->where('reference_id', $referenceId);
    }

    return $query->count();
}

function recordAdvance($test, float $amount, string $method = 'cash'): AdmissionAdvance
{
    $advance = $test->admission->advances()->create([
        'patient_id' => $test->patient->id,
        'amount' => $amount,
        'payment_date' => now()->toDateString(),
        'payment_method' => $method,
        'reference_number' => 'ADV-REF-'.$amount,
        'notes' => 'Test advance',
        'received_by' => $test->user->id,
    ]);

    AccountingService::postAdmissionAdvanceEntry($advance);

    return $advance;
}

function addDraftCharge(Bill $bill, float $amount, string $description = 'Service charge'): void
{
    $bill->billItems()->create([
        'description' => $description,
        'quantity' => 1,
        'unit_price' => $amount,
        'total_price' => $amount,
        'item_category' => 'ipd',
    ]);
    $bill->calculateTotals();
}

it('does not create bill journal entries while the ipd bill is still a draft', function () {
    addDraftCharge($this->bill, 5000);

    expect(countJournalEntries('Bill', $this->bill->id))->toBe(0)
        ->and($this->bill->fresh()->isDraft())->toBeTrue();
});

it('creates admission advance journal entries when advances are received but not bill entries', function () {
    $first = recordAdvance($this, 10000);
    $second = recordAdvance($this, 5000);

    addDraftCharge($this->bill, 8000);

    expect(countJournalEntries('AdmissionAdvance', $first->id))->toBe(1)
        ->and(countJournalEntries('AdmissionAdvance', $second->id))->toBe(1)
        ->and(countJournalEntries('Bill', $this->bill->id))->toBe(0)
        ->and($this->admission->advances()->count())->toBe(2)
        ->and($this->admission->total_advances)->toBe(15000.0)
        ->and($this->admission->draft_bill_charges)->toBe(8000.0)
        ->and($this->admission->credit_balance)->toBe(7000.0);
});

it('preserves visit link and draft status when updating charges through bill edit', function () {
    addDraftCharge($this->bill, 2500);

    $controller = app(BillController::class);
    $request = Request::create('/bills/'.$this->bill->id, 'PUT', [
        'patient_id' => $this->patient->id,
        'visit_id' => $this->visit->id,
        'bill_date' => now()->toDateString(),
        'bill_type' => 'ipd',
        'tax_amount' => 0,
        'discount_type' => 'fixed',
        'discount_amount' => 0,
        'discount_percentage' => 0,
        'notes' => 'Updated draft',
        'items' => [[
            'description' => 'Updated room charge',
            'quantity' => 1,
            'unit_price' => 4500,
        ]],
    ]);
    $request->setUserResolver(fn () => $this->user);

    $controller->update(
        \App\Http\Requests\UpdateBillRequest::createFrom($request),
        $this->bill->fresh()
    );

    $bill = $this->bill->fresh();

    expect($bill->isDraft())->toBeTrue()
        ->and($bill->visit_id)->toBe($this->visit->id)
        ->and((float) $bill->total_amount)->toBe(4500.0)
        ->and(countJournalEntries('Bill', $bill->id))->toBe(0)
        ->and($this->admission->fresh()->draft_bill_charges)->toBe(4500.0);
});

it('creates bill journal entry only when finalized at discharge', function () {
    recordAdvance($this, 12000);
    addDraftCharge($this->bill, 9000);

    expect(countJournalEntries('Bill', $this->bill->id))->toBe(0);

    IpdDischargeBillingService::finalizeForDischarge(
        $this->visit->fresh(['admission.advances', 'draftBill']),
        'cash'
    );

    expect(countJournalEntries('Bill', $this->bill->id))->toBe(1)
        ->and($this->bill->fresh()->isDraft())->toBeFalse();
});

it('records advance application as a bill payment and journal entry at finalization', function () {
    recordAdvance($this, 8000);
    addDraftCharge($this->bill, 5000);

    $result = IpdDischargeBillingService::finalizeForDischarge(
        $this->visit->fresh(['admission.advances', 'draftBill']),
        'cash'
    );

    $bill = $result['bill']->fresh(['payments']);

    /** @var Payment $advancePayment */
    $advancePayment = $bill->payments()->where('payment_method', 'advance')->first();

    expect($advancePayment)->not->toBeNull()
        ->and((float) $advancePayment->amount)->toBe(5000.0)
        ->and($advancePayment->notes)->toContain('advance')
        ->and(countJournalEntries('Payment', $advancePayment->id))->toBe(1)
        ->and((float) $bill->paid_amount)->toBe(5000.0)
        ->and($bill->status)->toBe('paid');
});

it('creates refund journal entry and stores refund on admission not on bill when advances exceed bill total', function () {
    recordAdvance($this, 20000);
    addDraftCharge($this->bill, 7500);

    IpdDischargeBillingService::finalizeForDischarge(
        $this->visit->fresh(['admission.advances', 'draftBill']),
        'cash'
    );

    $bill = $this->bill->fresh();
    $admission = $this->admission->fresh();

    expect(countJournalEntries('AdmissionRefund', $admission->id))->toBe(1)
        ->and((float) $admission->refund_amount)->toBe(12500.0)
        ->and($admission->refund_method)->toBe('cash')
        ->and($admission->refunded_at)->not->toBeNull()
        ->and($bill->getAttributes())->not->toHaveKey('refund_amount')
        ->and((float) $bill->paid_amount)->toBe(7500.0);
});

it('does not create refund journal entry when advances exactly match the bill total', function () {
    recordAdvance($this, 6000);
    addDraftCharge($this->bill, 6000);

    IpdDischargeBillingService::finalizeForDischarge(
        $this->visit->fresh(['admission.advances', 'draftBill'])
    );

    expect(countJournalEntries('AdmissionRefund', $this->admission->id))->toBe(0)
        ->and((float) $this->admission->fresh()->refund_amount)->toBe(0.0)
        ->and($this->bill->fresh()->status)->toBe('paid');
});

it('leaves bill partial with due amount when advances are less than bill total', function () {
    recordAdvance($this, 3000);
    addDraftCharge($this->bill, 9000);

    $result = IpdDischargeBillingService::finalizeForDischarge(
        $this->visit->fresh(['admission.advances', 'draftBill'])
    );

    $bill = $result['bill']->fresh();

    expect($bill->status)->toBe('partial')
        ->and((float) $bill->paid_amount)->toBe(3000.0)
        ->and((float) $bill->due_amount)->toBe(6000.0)
        ->and(countJournalEntries('AdmissionRefund', $this->admission->id))->toBe(0);
});

it('posts additional settlement payment journal when extra amount is collected at discharge', function () {
    recordAdvance($this, 2000);
    addDraftCharge($this->bill, 5000);

    $result = IpdDischargeBillingService::finalizeForDischarge(
        $this->visit->fresh(['admission.advances', 'draftBill']),
        null,
        3000,
        'cash'
    );

    $bill = $result['bill']->fresh(['payments']);
    $cashPayment = $bill->payments()->where('payment_method', 'cash')->first();

    expect((float) $bill->paid_amount)->toBe(5000.0)
        ->and($bill->status)->toBe('paid')
        ->and($cashPayment)->not->toBeNull()
        ->and((float) $cashPayment->amount)->toBe(3000.0)
        ->and(countJournalEntries('Payment', $cashPayment->id))->toBe(1);
});

it('maintains a single draft bill and recovers orphaned drafts with charges', function () {
    addDraftCharge($this->bill, 3200);

    $this->bill->update(['visit_id' => null]);

    $resolved = IpdDraftBillService::ensureForVisit($this->visit);

    expect($resolved->id)->toBe($this->bill->id)
        ->and($resolved->visit_id)->toBe($this->visit->id)
        ->and((float) $resolved->total_amount)->toBe(3200.0)
        ->and($this->visit->bills()->where('status', 'draft')->count())->toBe(1);
});

it('keeps full advance payment audit trail separate from bill refund fields', function () {
    $advanceOne = recordAdvance($this, 12000);
    $advanceTwo = recordAdvance($this, 8000);
    addDraftCharge($this->bill, 10000);

    IpdDischargeBillingService::finalizeForDischarge(
        $this->visit->fresh(['admission.advances', 'draftBill']),
        'bank_transfer'
    );

    $advances = $this->admission->fresh()->advances()->orderBy('id')->get();

    expect($advances)->toHaveCount(2)
        ->and((float) $advances[0]->amount)->toBe(12000.0)
        ->and($advances[0]->reference_number)->toBe('ADV-REF-12000')
        ->and((float) $advances[1]->amount)->toBe(8000.0)
        ->and(countJournalEntries('AdmissionAdvance', $advanceOne->id))->toBe(1)
        ->and(countJournalEntries('AdmissionAdvance', $advanceTwo->id))->toBe(1)
        ->and((float) $this->admission->fresh()->refund_amount)->toBe(10000.0)
        ->and($this->admission->fresh()->refund_method)->toBe('bank_transfer');
});
