<?php

use App\Models\Admission;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\IpdConsultantVisit;
use App\Models\IpdGpeRecord;
use App\Models\Patient;
use App\Models\PatientComplaint;
use App\Models\User;
use App\Models\Visit;
use App\Models\Bed;
use App\Models\Ward;
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
        'doctor_id' => $this->doctor->id,
        'visit_type' => 'ipd',
        'status' => 'admitted',
        'visit_datetime' => now(),
    ]);
});

it('assigns a duty doctor to an ipd visit', function () {
    $response = $this->post(route('visits.duty-doctor', $this->visit), [
        'duty_doctor_id' => $this->doctor->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($this->visit->fresh()->duty_doctor_id)->toBe($this->doctor->id);
});

it('rejects duty doctor assignment for opd visits', function () {
    $opd = Visit::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'visit_type' => 'opd',
        'status' => 'registered',
        'visit_datetime' => now(),
    ]);

    $response = $this->post(route('visits.duty-doctor', $opd), [
        'duty_doctor_id' => $this->doctor->id,
    ]);

    $response->assertSessionHasErrors();
});

it('stores multiple gpe records for the same ipd visit', function () {
    $this->post(route('visits.gpe-records.store', $this->visit), [
        'doctor_id' => $this->doctor->id,
        'gpe_chest' => 'Clear',
        'remarks' => 'First examination',
    ])->assertRedirect()->assertSessionHas('success');

    $this->post(route('visits.gpe-records.store', $this->visit), [
        'doctor_id' => $this->doctor->id,
        'gpe_abdomen' => 'Soft',
        'remarks' => 'Follow-up examination',
    ])->assertRedirect()->assertSessionHas('success');

    $records = IpdGpeRecord::where('visit_id', $this->visit->id)->get();

    expect($records)->toHaveCount(2)
        ->and($records->pluck('remarks')->all())->toContain('First examination', 'Follow-up examination');
});

it('stores consultant visits with consultant identity and timestamp', function () {
    $seenAt = now()->subHours(2)->format('Y-m-d H:i:s');

    $response = $this->post(route('visits.consultant-visits.store', $this->visit), [
        'consultant_doctor_id' => $this->consultant->id,
        'visit_notes' => 'Reviewed patient condition',
        'orders' => 'Continue current medications',
        'consultant_seen_at' => $seenAt,
    ]);

    $response->assertRedirect()->assertSessionHas('success');

    $record = IpdConsultantVisit::first();

    expect($record)->not->toBeNull()
        ->and($record->consultant_doctor_id)->toBe($this->consultant->id)
        ->and($record->visit_notes)->toBe('Reviewed patient condition')
        ->and($record->orders)->toBe('Continue current medications')
        ->and($record->consultant_seen_at->format('Y-m-d H:i'))->toBe(now()->subHours(2)->format('Y-m-d H:i'));
});

it('syncs active complaints from ipd consultation presenting complaints', function () {
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

it('prevents a doctor from updating another consultants visit record', function () {
    $otherDoctorUser = User::create([
        'name' => 'Other Doctor User',
        'email' => 'other-doctor@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $otherDoctor = Doctor::create([
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

    $consultantVisit = IpdConsultantVisit::create([
        'visit_id' => $this->visit->id,
        'consultant_doctor_id' => $this->consultant->id,
        'recorded_by' => $this->user->id,
        'visit_notes' => 'Initial notes',
        'orders' => 'Initial orders',
        'status' => 'pending',
        'consultant_seen_at' => now(),
    ]);

    Permission::findOrCreate('edit visits', 'web');
    $otherDoctorUser->givePermissionTo('edit visits');

    $this->actingAs($otherDoctorUser);

    $response = $this->put(route('visits.consultant-visits.update', [$this->visit, $consultantVisit]), [
        'visit_notes' => 'Unauthorized edit',
        'orders' => 'Unauthorized orders',
        'status' => 'completed',
    ]);

    $response->assertSessionHasErrors();

    expect($consultantVisit->fresh()->visit_notes)->toBe('Initial notes');
});
