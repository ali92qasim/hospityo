<?php

use App\Http\Controllers\ReportController;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    config(['visits.dual_write_enabled' => false]);

    Permission::findOrCreate('view reports', 'web');

    $this->user = User::create([
        'name' => 'Revenue Report User',
        'email' => 'revenue-report-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo(['view reports']);

    $this->patient = Patient::create([
        'name' => 'Revenue Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03001234567',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03001234568',
        'emergency_relation' => 'Sibling',
    ]);

    $this->visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'status' => 'registered',
        'visit_datetime' => now(),
    ]);

    $this->service = Service::create([
        'name' => 'Consultation',
        'code' => 'CONS-REV',
        'category' => 'consultation',
        'price' => 1000,
        'is_active' => true,
    ]);

    $this->labTest = LabTest::create([
        'code' => 'CBC-001',
        'name' => 'COMPLETE BLOOD COUNT(CBC)',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 1000,
        'is_active' => true,
    ]);

    $this->bill = Bill::create([
        'bill_number' => 'BILL-REV-001',
        'patient_id' => $this->patient->id,
        'visit_id' => $this->visit->id,
        'bill_type' => 'opd',
        'bill_date' => today(),
        'subtotal' => 3000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 3000,
        'paid_amount' => 3000,
        'due_amount' => 0,
        'status' => 'paid',
        'created_by' => $this->user->id,
    ]);

    BillItem::create([
        'bill_id' => $this->bill->id,
        'service_id' => $this->service->id,
        'item_category' => 'opd',
        'description' => 'Consultation',
        'quantity' => 1,
        'unit_price' => 1000,
        'total_price' => 1000,
    ]);

    BillItem::create([
        'bill_id' => $this->bill->id,
        'lab_test_id' => $this->labTest->id,
        'item_category' => 'lab',
        'description' => 'COMPLETE BLOOD COUNT(CBC)',
        'quantity' => 2,
        'unit_price' => 1000,
        'total_price' => 2000,
    ]);
});

it('loads bill items for revenue grouping with lab and imaging relations', function () {
    $items = BillItem::with(['service', 'labTest', 'imagingStudy'])->get();

    expect($items)->toHaveCount(2);
});

it('builds the revenue report without the removed investigation relation', function () {
    $response = app(ReportController::class)->revenue(new Request([
        'start_date' => today()->subYear()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
    ]));

    expect($response->getData()['totals']['total_revenue'])->toBe(3000.0)
        ->and($response->getData()['serviceRevenue'])->toHaveCount(2);
});

it('renders the revenue report page successfully', function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user)
        ->get(route('reports.revenue', [
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
        ]))
        ->assertOk()
        ->assertSee('Revenue Report');
});
