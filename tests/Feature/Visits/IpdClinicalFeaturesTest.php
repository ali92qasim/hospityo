<?php

use App\Models\Admission;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\IpdCareTeam;
use App\Models\IpdDoctorVisitNote;
use App\Models\IpdGpeRecord;
use App\Models\LabOrder;
use App\Models\LabTest;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\PatientComplaint;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use App\Models\Bed;
use App\Models\Ward;
use App\Services\IpdClinicalService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'IPD Clinical User',
        'email' => 'ipd-clinical@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('edit visits', 'web');
    Permission::findOrCreate('view visits', 'web');
    $this->user->givePermissionTo(['edit visits', 'view visits']);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Clinical Patient',
        'gender' => 'male',
        'age' => 45,
        'phone' => '03001112233',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03004445566',
        'emergency_relation' => 'Spouse',
    ]);

    $department = Department::create(['name' => 'Medicine', 'code' => 'MED', 'status' => 'active']);

    $this->doctor = Doctor::create([
        'name' => 'Dr. Primary',
        'doctor_no' => 'DOC-001',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03006667788',
        'email' => 'dr-primary@example.com',
        'gender' => 'male',
        'experience_years' => 8,
        'consultation_fee' => 1500,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $this->consultant = Doctor::create([
        'name' => 'Dr. Consultant',
        'doctor_no' => 'DOC-002',
        'specialization' => 'Cardiology',
        'qualification' => 'FCPS',
        'phone' => '03009990000',
        'email' => 'dr-consultant@example.com',
        'gender' => 'female',
        'experience_years' => 12,
        'consultation_fee' => 3000,
        'shift_start' => '10:00:00',
        'shift_end' => '14:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $ward = Ward::create([
        'name' => 'General Ward',
        'department_id' => $department->id,
        'capacity' => 10,
        'ward_type' => 'general',
        'status' => 'active',
    ]);

    $this->bed = Bed::create([
        'ward_id' => $ward->id,
        'bed_number' => 'GW-02',
        'bed_type' => 'general',
        'daily_rate' => 2000,
        'status' => 'available',
    ]);

    $this->visit = Visit::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => null,
        'visit_type' => 'ipd',
        'status' => 'admitted',
        'visit_datetime' => now(),
    ]);
});

it('makes the first doctor added to care team primary automatically', function () {
    $this->post(route('visits.care-team.store', $this->visit), [
        'doctor_id' => $this->doctor->id,
    ])->assertRedirect()->assertSessionHas('success');

    $member = IpdCareTeam::first();

    expect($member)->not->toBeNull()
        ->and($member->is_primary)->toBeTrue()
        ->and($member->doctor_id)->toBe($this->doctor->id);
});

it('does not make the second doctor added primary', function () {
    $this->post(route('visits.care-team.store', $this->visit), [
        'doctor_id' => $this->doctor->id,
    ])->assertSessionHas('success');

    $this->post(route('visits.care-team.store', $this->visit), [
        'doctor_id' => $this->consultant->id,
    ])->assertSessionHas('success');

    expect(IpdCareTeam::where('visit_id', $this->visit->id)->active()->where('is_primary', true)->count())->toBe(1)
        ->and(IpdCareTeam::where('visit_id', $this->visit->id)->active()->where('doctor_id', $this->consultant->id)->first()->is_primary)->toBeFalse();
});

it('prevents duplicate active care team membership with a validation message', function () {
    $this->post(route('visits.care-team.store', $this->visit), [
        'doctor_id' => $this->doctor->id,
    ])->assertSessionHas('success');

    $response = $this->post(route('visits.care-team.store', $this->visit), [
        'doctor_id' => $this->doctor->id,
    ]);

    $response->assertSessionHasErrors('doctor_id');
    expect(IpdCareTeam::where('visit_id', $this->visit->id)->active()->count())->toBe(1);
});

it('rejects care team assignment for opd visits', function () {
    $opd = Visit::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'visit_type' => 'opd',
        'status' => 'registered',
        'visit_datetime' => now(),
    ]);

    $this->post(route('visits.care-team.store', $opd), [
        'doctor_id' => $this->doctor->id,
    ])->assertSessionHasErrors();
});

it('does not auto-promote another doctor when the primary is removed', function () {
    $this->post(route('visits.care-team.store', $this->visit), ['doctor_id' => $this->doctor->id]);
    $this->post(route('visits.care-team.store', $this->visit), ['doctor_id' => $this->consultant->id]);

    $primaryMember = IpdCareTeam::where('visit_id', $this->visit->id)->active()->where('doctor_id', $this->doctor->id)->first();

    $this->delete(route('visits.care-team.remove', [$this->visit, $primaryMember]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(IpdCareTeam::where('visit_id', $this->visit->id)->active()->where('is_primary', true)->count())->toBe(0)
        ->and(IpdCareTeam::where('visit_id', $this->visit->id)->active()->where('doctor_id', $this->consultant->id)->first()->is_primary)->toBeFalse();
});

it('transfers primary flag via set primary doctor', function () {
    $this->post(route('visits.care-team.store', $this->visit), ['doctor_id' => $this->doctor->id]);
    $this->post(route('visits.care-team.store', $this->visit), ['doctor_id' => $this->consultant->id]);

    $this->post(route('visits.care-team.primary', $this->visit), [
        'doctor_id' => $this->consultant->id,
    ])->assertRedirect()->assertSessionHas('success');

    expect(IpdCareTeam::where('visit_id', $this->visit->id)->active()->where('is_primary', true)->value('doctor_id'))
        ->toBe($this->consultant->id);
});

it('re-adds a removed doctor as a new care team row', function () {
    $this->post(route('visits.care-team.store', $this->visit), ['doctor_id' => $this->doctor->id]);
    $member = IpdCareTeam::first();
    $this->delete(route('visits.care-team.remove', [$this->visit, $member]));

    $this->post(route('visits.care-team.store', $this->visit), ['doctor_id' => $this->doctor->id])
        ->assertSessionHas('success');

    expect(IpdCareTeam::where('visit_id', $this->visit->id)->count())->toBe(2)
        ->and(IpdCareTeam::where('visit_id', $this->visit->id)->active()->count())->toBe(1);
});

it('rejects gpe when no care team members are assigned', function () {
    $this->post(route('visits.gpe-records.store', $this->visit), [
        'doctor_id' => $this->doctor->id,
        'gpe_chest' => 'Clear',
    ])->assertSessionHasErrors('doctor_id');

    expect(IpdGpeRecord::count())->toBe(0);
});

it('rejects gpe for a doctor not on the care team', function () {
    $this->post(route('visits.care-team.store', $this->visit), ['doctor_id' => $this->doctor->id]);

    $this->post(route('visits.gpe-records.store', $this->visit), [
        'doctor_id' => $this->consultant->id,
        'gpe_chest' => 'Clear',
    ])->assertSessionHasErrors('doctor_id');
});

it('stores doctor visit notes with server-generated visited_at', function () {
    $this->post(route('visits.care-team.store', $this->visit), ['doctor_id' => $this->consultant->id]);

    $response = $this->post(route('visits.doctor-visit-notes.store', $this->visit), [
        'doctor_id' => $this->consultant->id,
        'notes' => 'Reviewed patient condition',
        'visited_at' => now()->subHours(5)->format('Y-m-d H:i:s'),
    ]);

    $response->assertRedirect()->assertSessionHas('success');

    $record = IpdDoctorVisitNote::first();

    expect($record)->not->toBeNull()
        ->and($record->doctor_id)->toBe($this->consultant->id)
        ->and($record->notes)->toBe('Reviewed patient condition')
        ->and($record->visited_at->greaterThan(now()->subMinute()))->toBeTrue()
        ->and($record->visited_at->format('Y-m-d H:i'))->not->toBe(now()->subHours(5)->format('Y-m-d H:i'));
});

it('rejects doctor visit notes for a doctor not on the active care team', function () {
    $this->post(route('visits.doctor-visit-notes.store', $this->visit), [
        'doctor_id' => $this->consultant->id,
        'notes' => 'Should fail',
    ])->assertSessionHasErrors('doctor_id');

    expect(IpdDoctorVisitNote::count())->toBe(0);
});

it('requires prescription doctor_id to be an active care team member for ipd', function () {
    $medicine = Medicine::create([
        'name' => 'Paracetamol',
        'generic_name' => 'Paracetamol',
        'category_id' => null,
        'unit' => 'tablet',
        'strength' => '500mg',
        'status' => 'active',
        'manage_stock' => false,
        'selling_price' => 10,
    ]);

    $this->post(route('visits.prescription', $this->visit), [
        'doctor_id' => $this->doctor->id,
        'fulfillment_type' => 'in_house',
        'medicines' => [
            ['medicine_id' => $medicine->id, 'quantity' => 1],
        ],
    ])->assertSessionHasErrors('doctor_id');

    $this->post(route('visits.care-team.store', $this->visit), ['doctor_id' => $this->doctor->id]);

    $this->post(route('visits.prescription', $this->visit), [
        'doctor_id' => $this->consultant->id,
        'fulfillment_type' => 'in_house',
        'medicines' => [
            ['medicine_id' => $medicine->id, 'quantity' => 1],
        ],
    ])->assertSessionHasErrors('doctor_id');

    $this->post(route('visits.prescription', $this->visit), [
        'doctor_id' => $this->doctor->id,
        'fulfillment_type' => 'in_house',
        'medicines' => [
            ['medicine_id' => $medicine->id, 'quantity' => 1],
        ],
    ])->assertRedirect()->assertSessionHas('success');

    expect(Prescription::first()->doctor_id)->toBe($this->doctor->id);
});

it('requires investigation order doctor_id to be an active care team member for ipd', function () {
    $labTest = LabTest::create([
        'name' => 'CBC',
        'code' => 'CBC-001',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 500,
        'turnaround_time' => '24',
        'is_active' => true,
    ]);

    $payload = [
        'doctor_id' => $this->doctor->id,
        'tests' => [
            [
                'lab_test_id' => $labTest->id,
                'quantity' => 1,
                'priority' => 'routine',
            ],
        ],
    ];

    $this->post(route('visits.order-multiple-lab-tests', $this->visit), $payload)
        ->assertSessionHasErrors('doctor_id');

    $this->post(route('visits.care-team.store', $this->visit), ['doctor_id' => $this->doctor->id]);

    $this->post(route('visits.order-multiple-lab-tests', $this->visit), $payload)
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(LabOrder::first()->doctor_id)->toBe($this->doctor->id);
});

it('serializes concurrent add care team calls so only one becomes primary', function () {
    DB::connection('tenant')->transaction(function () {
        IpdClinicalService::addDoctorToCareTeam($this->visit, $this->doctor, $this->user->id);

        DB::connection('tenant')->transaction(function () {
            IpdClinicalService::addDoctorToCareTeam($this->visit, $this->consultant, $this->user->id);
        });
    });

    expect(IpdCareTeam::where('visit_id', $this->visit->id)->active()->where('is_primary', true)->count())->toBe(1)
        ->and(IpdCareTeam::where('visit_id', $this->visit->id)->active()->where('doctor_id', $this->doctor->id)->first()->is_primary)->toBeTrue()
        ->and(IpdCareTeam::where('visit_id', $this->visit->id)->active()->where('doctor_id', $this->consultant->id)->first()->is_primary)->toBeFalse();
});

it('enforces duplicate active membership at the database index level', function () {
    IpdCareTeam::create([
        'visit_id' => $this->visit->id,
        'doctor_id' => $this->doctor->id,
        'is_primary' => true,
        'added_at' => now(),
        'added_by' => $this->user->id,
    ]);

    expect(fn () => IpdCareTeam::create([
        'visit_id' => $this->visit->id,
        'doctor_id' => $this->doctor->id,
        'is_primary' => false,
        'added_at' => now(),
        'added_by' => $this->user->id,
    ]))->toThrow(QueryException::class);
});

it('enforces single active primary at the database index level', function () {
    IpdCareTeam::create([
        'visit_id' => $this->visit->id,
        'doctor_id' => $this->doctor->id,
        'is_primary' => true,
        'added_at' => now(),
        'added_by' => $this->user->id,
    ]);

    expect(fn () => IpdCareTeam::create([
        'visit_id' => $this->visit->id,
        'doctor_id' => $this->consultant->id,
        'is_primary' => true,
        'added_at' => now(),
        'added_by' => $this->user->id,
    ]))->toThrow(QueryException::class);
});

it('syncs active complaints from ipd consultation presenting complaints', function () {
    $this->post(route('visits.care-team.store', $this->visit), ['doctor_id' => $this->doctor->id]);

    $response = $this->post(route('visits.consultation', $this->visit), [
        'presenting_complaints' => "Fever\nChest pain",
        'history' => 'Patient history',
    ]);

    $response->assertRedirect()->assertSessionHas('success');

    $complaints = PatientComplaint::where('patient_id', $this->patient->id)
        ->where('status', 'active')
        ->pluck('complaint')
        ->all();

    expect($complaints)->toContain('Fever', 'Chest pain');
});

it('stores and resolves patient complaints for ipd visits', function () {
    $response = $this->post(route('visits.complaints.store', $this->visit), [
        'complaint' => 'Persistent cough',
    ]);

    $response->assertRedirect()->assertSessionHas('success');

    $complaint = PatientComplaint::first();

    expect($complaint->complaint)->toBe('Persistent cough')
        ->and($complaint->status)->toBe('active');

    $this->post(route('visits.complaints.resolve', [$this->visit, $complaint]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($complaint->fresh()->status)->toBe('resolved');
});

it('prevents a doctor from updating another doctors visit note', function () {
    $this->post(route('visits.care-team.store', $this->visit), ['doctor_id' => $this->consultant->id]);

    $visitNote = IpdDoctorVisitNote::create([
        'visit_id' => $this->visit->id,
        'doctor_id' => $this->consultant->id,
        'created_by' => $this->user->id,
        'notes' => 'Initial notes',
        'orders' => 'Initial orders',
        'status' => 'pending',
        'visited_at' => now(),
    ]);

    $otherDoctorUser = User::create([
        'name' => 'Other Doctor User',
        'email' => 'other-doctor@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Doctor::create([
        'name' => 'Dr. Other',
        'doctor_no' => 'DOC-003',
        'specialization' => 'Neurology',
        'qualification' => 'MBBS',
        'phone' => '03001231231',
        'email' => 'dr-other@example.com',
        'gender' => 'male',
        'experience_years' => 6,
        'consultation_fee' => 2000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $this->doctor->department_id,
        'user_id' => $otherDoctorUser->id,
    ]);

    $otherDoctorUser->givePermissionTo('edit visits');
    $this->actingAs($otherDoctorUser);

    $this->put(route('visits.doctor-visit-notes.update', [$this->visit, $visitNote]), [
        'notes' => 'Unauthorized edit',
        'status' => 'completed',
    ])->assertSessionHasErrors();

    expect($visitNote->fresh()->notes)->toBe('Initial notes');
});

it('records gpe as the logged-in care team doctor without a dropdown', function () {
    $doctorUser = User::create([
        'name' => 'Care Team Doctor User',
        'email' => 'care-team-doctor@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->doctor->update(['user_id' => $doctorUser->id]);
    $doctorUser->givePermissionTo('edit visits');
    $this->actingAs($doctorUser);

    $this->post(route('visits.care-team.store', $this->visit), [
        'doctor_id' => $this->doctor->id,
    ])->assertSessionHas('success');

    $this->post(route('visits.gpe-records.store', $this->visit), [
        'gpe_chest' => 'Clear',
        'remarks' => 'Duty round',
    ])->assertRedirect()->assertSessionHas('success');

    expect(IpdGpeRecord::first()->doctor_id)->toBe($this->doctor->id);
});
