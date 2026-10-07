<?php

use App\Models\Account;
use App\Models\Bill;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\InventoryTransaction;
use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| Prescriptions + pharmacy POS route scoping (RBAC wave 1, Task 11, D1)
|--------------------------------------------------------------------------
| Prescribing leaves `edit visits` for `create prescriptions` (no manage
| pharmacy superset: prescribing is clinical). The prescription list/show
| take `view prescriptions` + manage pharmacy. Dispense and the POS terminal
| take `dispense pharmacy` + manage pharmacy; `view pos` opens nothing (AC-2).
*/

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
    $this->withoutVite();

    phRxTenant();
    phRxSeedAccounts();

    $this->owner = User::create([
        'name' => 'PhRx Owner',
        'email' => 'phrx-owner@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $unit = Unit::create([
        'name' => 'PhRx Tablet',
        'abbreviation' => 'PRT',
        'conversion_factor' => 1,
        'type' => 'solid',
        'is_active' => true,
    ]);

    $category = MedicineCategory::create(['code' => 'PRX', 'name' => 'PhRx Tablets', 'is_active' => true]);

    $this->medicine = Medicine::create([
        'name' => 'PhRx Medicine',
        'sku' => 'PHRX-MED-001',
        'category_id' => $category->id,
        'base_unit_id' => $unit->id,
        'purchase_unit_id' => $unit->id,
        'dispensing_unit_id' => $unit->id,
        'manage_stock' => true,
        'status' => 'active',
        'selling_price' => 50,
    ]);

    // The stocked batch: dispense and checkout consume its remaining_quantity.
    $this->batch = InventoryTransaction::create([
        'medicine_id' => $this->medicine->id,
        'type' => 'stock_in',
        'quantity' => 20,
        'remaining_quantity' => 20,
        'unit_cost' => 10,
        'total_cost' => 200,
        'batch_no' => 'PHRX-B1',
        'expiry_date' => now()->addYear(),
        'created_by' => $this->owner->id,
    ]);

    $this->patient = Patient::create([
        'name' => 'PhRx Patient',
        'gender' => 'female',
        'age' => 31,
        'phone' => '03001230001',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03001230002',
        'emergency_relation' => 'Spouse',
    ]);

    $this->department = Department::create(['name' => 'PhRx Medicine', 'code' => 'PHRX', 'status' => 'active']);

    $this->doctor = phRxDoctor($this->department, 'Dr. PhRx', 'DOC-PHRX-1', 'dr-phrx@example.com');

    // OPD visit with an assigned doctor: the visit prescription panel is unlocked.
    $this->visit = Visit::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'with_doctor',
    ]);

    // A pending in-house prescription (2 units), ready for POS or dispense.
    $this->prescription = Prescription::create([
        'visit_id' => $this->visit->id,
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'fulfillment_type' => 'in_house',
        'status' => 'pending',
        'prescribed_date' => now(),
        'total_amount' => 100,
    ]);

    PrescriptionItem::create([
        'prescription_id' => $this->prescription->id,
        'medicine_id' => $this->medicine->id,
        'quantity' => 2,
        'unit_price' => 50,
        'total_price' => 100,
    ]);
});

function phRxTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    // Every module is enabled so no plan-level 403 can masquerade as a permission 403.
    $tenant->shouldReceive('hasModule')->andReturnTrue();

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function phRxSeedAccounts(): void
{
    Account::create(['code' => '1100', 'name' => 'Cash in Hand', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '4400', 'name' => 'Pharmacy Revenue', 'type' => 'revenue', 'is_system' => true]);
}

function phRxDoctor(Department $department, string $name, string $number, string $email): Doctor
{
    return Doctor::create([
        'name' => $name,
        'doctor_no' => $number,
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '0300'.random_int(1000000, 9999999),
        'email' => $email,
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);
}

function phRxUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'PhRx Scope User',
        'email' => 'phrx-scope-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

/**
 * Every live route: name => HTTP verb.
 */
function phRxRoutes(): array
{
    return [
        'pharmacy.pos.index' => 'get',
        'pharmacy.pos.medicines.search' => 'get',
        'pharmacy.pos.prescription' => 'get',
        'pharmacy.pos.checkout' => 'post',
        'visits.prescription' => 'post',
        'prescriptions.index' => 'get',
        'prescriptions.create' => 'get',
        'prescriptions.store' => 'post',
        'prescriptions.show' => 'get',
        'prescriptions.dispense' => 'post',
    ];
}

function phRxRouteParams($test, string $name): array
{
    return match ($name) {
        'pharmacy.pos.medicines.search' => ['q' => 'PhRx'],
        'pharmacy.pos.prescription', 'prescriptions.show', 'prescriptions.dispense' => ['prescription' => $test->prescription->id],
        'visits.prescription' => ['visit' => $test->visit->id],
        default => [],
    };
}

/** A valid payload for each write, so a request that passes the gate really writes. */
function phRxPayload($test, string $name): array
{
    return match ($name) {
        'pharmacy.pos.checkout' => [
            'mode' => 'prescription',
            'prescription_id' => $test->prescription->id,
            'patient_id' => $test->patient->id,
            'payment_amount' => 100,
            'payment_method' => 'cash',
        ],
        'visits.prescription' => [
            'medicines' => [['medicine_id' => $test->medicine->id, 'quantity' => 1]],
        ],
        'prescriptions.store' => [
            'visit_id' => $test->visit->id,
            'medicines' => [[
                'medicine_id' => $test->medicine->id,
                'quantity' => 1,
                'dosage' => '1 tab',
                'frequency' => 'BD',
                'duration' => '5 days',
            ]],
        ],
        default => [],
    };
}

function phRxRequest($test, string $name)
{
    $method = phRxRoutes()[$name];
    $url = route($name, phRxRouteParams($test, $name));

    return $method === 'get'
        ? $test->get($url)
        : $test->{$method}($url, phRxPayload($test, $name));
}

function phRxAssertPassed($test, $response, string $name): void
{
    if (phRxRoutes()[$name] === 'get') {
        $response->assertOk();

        return;
    }

    if ($name === 'pharmacy.pos.checkout') {
        $bill = Bill::where('prescription_id', $test->prescription->id)->first();
        expect($bill)->not->toBeNull();
        $response->assertRedirect(route('bills.print', $bill));

        return;
    }

    // The write ran: a redirect with the success flash, no validation or gate error.
    $response->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error')
        ->assertSessionHas('success');
}

function phRxPosRoutes(): array
{
    return ['pharmacy.pos.index', 'pharmacy.pos.medicines.search', 'pharmacy.pos.prescription', 'pharmacy.pos.checkout'];
}

function phRxDenyCases(): array
{
    $cases = [];

    // AC-2: view pos opens nothing; the terminal and checkout are dispense actions.
    foreach (phRxPosRoutes() as $name) {
        $cases["{$name} via view pos"] = [$name, ['view pos']];
    }

    // D1: edit visits no longer reaches any prescription route.
    foreach (['visits.prescription', 'prescriptions.create', 'prescriptions.store', 'prescriptions.index',
        'prescriptions.show', 'prescriptions.dispense'] as $name) {
        $cases["{$name} via edit visits"] = [$name, ['edit visits']];
    }

    // Prescribing is clinical: manage pharmacy is not a superset on it.
    foreach (['visits.prescription', 'prescriptions.create', 'prescriptions.store'] as $name) {
        $cases["{$name} via manage pharmacy"] = [$name, ['manage pharmacy']];
    }

    // D2: create does not imply view; prescribing does not imply dispensing.
    foreach (['prescriptions.index', 'prescriptions.show', 'prescriptions.dispense'] as $name) {
        $cases["{$name} via create prescriptions"] = [$name, ['create prescriptions']];
    }

    return $cases;
}

function phRxPositiveCases(): array
{
    $cases = [];

    foreach ([...phRxPosRoutes(), 'prescriptions.dispense'] as $name) {
        $cases["{$name} via dispense pharmacy"] = [$name, 'dispense pharmacy'];
        $cases["{$name} via manage pharmacy"] = [$name, 'manage pharmacy'];
    }
    foreach (['prescriptions.index', 'prescriptions.show'] as $name) {
        $cases["{$name} via view prescriptions"] = [$name, 'view prescriptions'];
        $cases["{$name} via manage pharmacy"] = [$name, 'manage pharmacy'];
    }
    foreach (['visits.prescription', 'prescriptions.create', 'prescriptions.store'] as $name) {
        $cases["{$name} via create prescriptions"] = [$name, 'create prescriptions'];
    }

    return $cases;
}

// ── (a) Deny: a holder that passes today (or must never pass) gets 403 ────

it('forbids a wrong permission on each prescription and pos route', function (string $name, array $permissions) {
    $this->actingAs(phRxUser($permissions));

    phRxRequest($this, $name)->assertForbidden();
})->with(phRxDenyCases());

// ── (b) Positive control: exactly the new permission passes ───────────────

it('allows exactly the new permission on each prescription and pos route', function (string $name, string $permission) {
    $this->actingAs(phRxUser([$permission]));

    phRxAssertPassed($this, phRxRequest($this, $name), $name);
})->with(phRxPositiveCases());

// ── (c) Side effects: a blocked write writes nothing ──────────────────────
// The "nothing written" assertions run before the status assertion, so that
// before the fix these tests fail because the write actually happened.

it('creates no bill and moves no stock when view pos checks out a prescription', function () {
    $this->actingAs(phRxUser(['view pos']));
    $billsBefore = Bill::count();
    $transactionsBefore = InventoryTransaction::count();

    $response = phRxRequest($this, 'pharmacy.pos.checkout');

    expect(Bill::count())->toBe($billsBefore)
        ->and($this->prescription->fresh()->status)->toBe('pending')
        ->and($this->batch->fresh()->remaining_quantity)->toBe(20)
        ->and(InventoryTransaction::count())->toBe($transactionsBefore);
    $response->assertForbidden();
});

it('leaves the prescription pending and the stock unmoved when edit visits dispenses it', function () {
    $this->actingAs(phRxUser(['edit visits']));
    $transactionsBefore = InventoryTransaction::count();

    $response = phRxRequest($this, 'prescriptions.dispense');

    $prescription = $this->prescription->fresh();
    expect($prescription->status)->toBe('pending')
        ->and($prescription->dispensed_date)->toBeNull()
        ->and($this->batch->fresh()->remaining_quantity)->toBe(20)
        ->and(InventoryTransaction::count())->toBe($transactionsBefore);
    $response->assertForbidden();
});

it('creates no prescription when edit visits prescribes from the visit', function () {
    $this->actingAs(phRxUser(['edit visits']));
    $prescriptionsBefore = Prescription::count();

    $response = phRxRequest($this, 'visits.prescription');

    expect(Prescription::count())->toBe($prescriptionsBefore);
    $response->assertForbidden();
});

// Proves the dispense side-effect assertion is meaningful: a permitted dispense writes.
it('dispenses and reduces stock for a dispense pharmacy user', function () {
    $this->actingAs(phRxUser(['dispense pharmacy']));
    $transactionsBefore = InventoryTransaction::count();

    phRxRequest($this, 'prescriptions.dispense')
        ->assertRedirect()
        ->assertSessionHas('success');

    $prescription = $this->prescription->fresh();
    expect($prescription->status)->toBe('dispensed')
        ->and($prescription->dispensed_date)->not->toBeNull()
        ->and($this->batch->fresh()->remaining_quantity)->toBe(18)
        ->and(InventoryTransaction::count())->toBe($transactionsBefore + 1);
});

// ── Visit workflow panel: the prescription form needs create prescriptions ─

it('shows the visit prescription form only to users who can create prescriptions', function (array $permissions, bool $visible) {
    $this->actingAs(phRxUser($permissions));

    $formAction = 'action="'.route('visits.prescription', $this->visit).'"';
    $response = $this->get(route('visits.workflow', $this->visit))->assertOk();

    $visible
        ? $response->assertSee($formAction, false)
        : $response->assertDontSee($formAction, false);
})->with([
    'with create prescriptions' => [['view visits', 'edit visits', 'create prescriptions'], true],
    'without create prescriptions' => [['view visits', 'edit visits'], false],
]);

// ── IPD: a non-doctor still prescribes on behalf of a care-team doctor ────

it('lets a non-doctor with create prescriptions prescribe on ipd for a picked care-team doctor', function () {
    $this->actingAs(phRxUser(['view visits', 'edit visits', 'create prescriptions']));

    $ipdVisit = Visit::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => null,
        'visit_type' => 'ipd',
        'status' => 'admitted',
        'visit_datetime' => now(),
    ]);
    $medicine = Medicine::create([
        'name' => 'PhRx IPD Medicine',
        'generic_name' => 'Paracetamol',
        'unit' => 'tablet',
        'strength' => '500mg',
        'status' => 'active',
        'manage_stock' => false,
        'selling_price' => 10,
    ]);

    $this->post(route('visits.care-team.store', $ipdVisit), ['doctor_id' => $this->doctor->id])
        ->assertRedirect();

    $this->post(route('visits.prescription', $ipdVisit), [
        'doctor_id' => $this->doctor->id,
        'medicines' => [['medicine_id' => $medicine->id, 'quantity' => 1]],
    ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');

    $prescription = Prescription::where('visit_id', $ipdVisit->id)->first();
    expect($prescription)->not->toBeNull()
        ->and($prescription->doctor_id)->toBe($this->doctor->id);
});

// ── Seeded roles (PH-1): Doctor and Nurse prescribe, neither dispenses ────

it('lets the seeded role prescribe from the visit but not dispense', function (string $roleName) {
    $this->seed(RolePermissionSeeder::class);

    $user = User::create([
        'name' => "PhRx {$roleName}",
        'email' => 'phrx-role-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $user->assignRole($roleName);
    $this->actingAs($user);

    $prescriptionsBefore = Prescription::count();

    phRxRequest($this, 'visits.prescription')
        ->assertStatus(302)
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
    expect(Prescription::count())->toBe($prescriptionsBefore + 1);

    phRxRequest($this, 'prescriptions.dispense')->assertForbidden();
    expect($this->prescription->fresh()->status)->toBe('pending');
})->with(['Doctor', 'Nurse']);

// ── Dead routes: edit / update / destroy have no controller methods ───────

it('does not register the dead prescription routes', function (string $name) {
    expect(Route::has($name))->toBeFalse();
})->with(['prescriptions.edit', 'prescriptions.update', 'prescriptions.destroy']);

it('references no dead prescription route in resources or app', function () {
    $pattern = "/route\\(\\s*['\"]prescriptions\\.(edit|update|destroy)['\"]/";
    $offenders = [];

    foreach ([resource_path(), app_path()] as $root) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (! $file->isFile() || ! in_array($file->getExtension(), ['php', 'js', 'ts', 'vue'], true)) {
                continue;
            }
            if (preg_match($pattern, (string) file_get_contents($file->getPathname()))) {
                $offenders[] = $file->getPathname();
            }
        }
    }

    expect($offenders)->toBe([]);
});
