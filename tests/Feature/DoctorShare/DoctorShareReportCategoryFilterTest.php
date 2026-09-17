<?php

use App\Http\Middleware\EnsureTenantActive;
use App\Http\Middleware\SetTenantTimezone;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Doctor;
use App\Models\DoctorShareItem;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        EnsureTenantActive::class,
        SetTenantTimezone::class,
    ]);
});

function bindDoctorShareCategoryReportTenant(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function doctorShareCategoryReportUser(): User
{
    Permission::findOrCreate('view share reports', 'web');

    $user = User::create([
        'name' => 'Doctor Share Category Report User',
        'email' => 'doctor-share-category-report-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo('view share reports');

    return $user;
}

/**
 * @return array{lab: int, opd: int}
 */
function createDoctorShareCategoryReportItems(User $user): array
{
    $doctor = Doctor::create([
        'name' => 'Dr. Category Report',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03001234567',
        'email' => 'category-report@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
    ]);

    $patient = Patient::create([
        'name' => 'Category Report Patient',
        'gender' => 'female',
        'age' => 30,
        'phone' => '03007654321',
        'emergency_name' => 'Emergency Contact',
        'emergency_phone' => '03001111111',
        'emergency_relation' => 'Sibling',
    ]);

    $bill = Bill::create([
        'patient_id' => $patient->id,
        'bill_number' => 'BILL-CATEGORY-REPORT',
        'bill_date' => today(),
        'bill_type' => 'opd',
        'subtotal' => 300,
        'total_amount' => 300,
        'due_amount' => 300,
        'status' => 'pending',
        'created_by' => $user->id,
    ]);

    $labBillItem = BillItem::create([
        'bill_id' => $bill->id,
        'item_category' => 'lab',
        'description' => 'Lab category line',
        'quantity' => 1,
        'unit_price' => 100,
        'total_price' => 100,
    ]);
    $opdBillItem = BillItem::create([
        'bill_id' => $bill->id,
        'item_category' => 'opd',
        'description' => 'OPD category line',
        'quantity' => 1,
        'unit_price' => 200,
        'total_price' => 200,
    ]);

    $labShareItem = DoctorShareItem::create([
        'bill_id' => $bill->id,
        'bill_item_id' => $labBillItem->id,
        'doctor_id' => $doctor->id,
        'rule_snapshot' => [],
        'base_amount' => 100,
        'share_amount' => 10,
        'status' => 'pending',
    ]);
    $opdShareItem = DoctorShareItem::create([
        'bill_id' => $bill->id,
        'bill_item_id' => $opdBillItem->id,
        'doctor_id' => $doctor->id,
        'rule_snapshot' => [],
        'base_amount' => 200,
        'share_amount' => 20,
        'status' => 'pending',
    ]);

    return ['lab' => $labShareItem->id, 'opd' => $opdShareItem->id];
}

it('filters an opd bill report by each bill line item category', function (string $category) {
    bindDoctorShareCategoryReportTenant(['settings', 'settings.doctor-share', 'visits', 'laboratory']);
    $user = doctorShareCategoryReportUser();
    $this->actingAs($user);
    $itemIds = createDoctorShareCategoryReportItems($user);
    $historyFingerprint = fingerprintDoctorShareHistory();

    $this->get(route('doctor-share.reports.index', ['bill_type' => $category]))
        ->assertOk()
        ->assertViewHas('details', function ($details) use ($category, $itemIds) {
            return $details->pluck('id')->all() === [$itemIds[$category]];
        });

    expect(fingerprintDoctorShareHistory())->toBe($historyFingerprint);
})->with(['lab', 'opd']);

it('rejects and does not list the investigation report token', function () {
    bindDoctorShareCategoryReportTenant(['settings', 'settings.doctor-share', 'laboratory', 'imaging']);
    $this->actingAs(doctorShareCategoryReportUser());

    $this->get(route('doctor-share.reports.index'))
        ->assertOk()
        ->assertDontSee('value="investigation"', false)
        ->assertSee('value="lab"', false)
        ->assertSee('value="imaging"', false);

    $this->get(route('doctor-share.reports.index', ['bill_type' => 'investigation']))
        ->assertForbidden();
});

it('rejects unknown report bill type tokens', function () {
    bindDoctorShareCategoryReportTenant(['settings', 'settings.doctor-share']);
    $this->actingAs(doctorShareCategoryReportUser());

    $this->get(route('doctor-share.reports.index', ['bill_type' => 'unknown']))
        ->assertForbidden();
});

it('gates the pharmacy report option and filter on the pharmacy module', function () {
    bindDoctorShareCategoryReportTenant(['settings', 'settings.doctor-share']);
    $this->actingAs(doctorShareCategoryReportUser());

    $this->get(route('doctor-share.reports.index'))
        ->assertOk()
        ->assertDontSee('value="pharmacy"', false);
    $this->get(route('doctor-share.reports.index', ['bill_type' => 'pharmacy']))
        ->assertForbidden();

    bindDoctorShareCategoryReportTenant(['settings', 'settings.doctor-share', 'pharmacy']);

    $this->get(route('doctor-share.reports.index'))
        ->assertOk()
        ->assertSee('value="pharmacy"', false);
    $this->get(route('doctor-share.reports.index', ['bill_type' => 'pharmacy']))
        ->assertOk();
});
