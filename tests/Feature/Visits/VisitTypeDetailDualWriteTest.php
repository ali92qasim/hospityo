<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\EmergencyVisit;
use App\Models\IpdVisit;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\VisitTypeDetailMismatchLogger;
use App\Services\VisitTypeDetailSyncService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    config(['visits.dual_write_enabled' => true]);

    $this->user = User::create([
        'name' => 'Dual Write User',
        'email' => 'dual-write@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('create visits', 'web');
    Permission::findOrCreate('edit visits', 'web');
    $this->user->givePermissionTo(['create visits', 'edit visits']);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Dual Write Patient',
        'gender' => 'female',
        'age' => 29,
        'phone' => '03006660001',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03006660002',
        'emergency_relation' => 'Parent',
    ]);

    $department = Department::create(['name' => 'Medicine', 'code' => 'MED-DW', 'status' => 'active']);

    $this->doctor = Doctor::create([
        'name' => 'Dr. Dual',
        'doctor_no' => 'DOC-DW-001',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03006660003',
        'email' => 'dr-dual@example.com',
        'gender' => 'female',
        'experience_years' => 7,
        'consultation_fee' => 1300,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);
});

it('createForVisit is idempotent when called twice for the same visit', function () {
    $cases = [
        'opd' => OpdVisit::class,
        'ipd' => IpdVisit::class,
        'emergency' => EmergencyVisit::class,
    ];

    foreach ($cases as $visitType => $childClass) {
        $visit = Visit::create([
            'patient_id' => $this->patient->id,
            'visit_type' => $visitType,
            'visit_datetime' => now(),
            'status' => 'registered',
        ]);

        VisitTypeDetailSyncService::createForVisit($visit);
        VisitTypeDetailSyncService::createForVisit($visit);

        expect($childClass::where('visit_id', $visit->id)->count())->toBe(1);
    }
});

it('store creates opd child row when dual write enabled', function () {
    $this->post(route('visits.store'), [
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now()->toDateTimeString(),
    ])->assertRedirect();

    $visit = Visit::latest('id')->first();

    expect(OpdVisit::where('visit_id', $visit->id)->exists())->toBeTrue();
});

it('assign doctor updates spine doctor_id only', function () {
    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
    ]);

    expect($visit->opdDetails)->not->toBeNull();

    $this->post(route('visits.assign-doctor', $visit), [
        'doctor_id' => $this->doctor->id,
    ])->assertRedirect();

    $visit->refresh();

    expect($visit->doctor_id)->toBe($this->doctor->id)
        ->and(Schema::connection('tenant')->hasColumn('opd_visits', 'doctor_id'))->toBeFalse();
});

it('logs mismatch when opd child row is missing', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn ($message, $context) => $message === 'visit_type_detail_mismatch'
            && ($context['field'] ?? null) === 'missing_child_row');

    config(['visits.log_child_mismatches' => true, 'visits.dual_write_enabled' => false]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
    ]);

    OpdVisit::where('visit_id', $visit->id)->delete();

    VisitTypeDetailMismatchLogger::audit($visit->fresh(['opdDetails']));
});
