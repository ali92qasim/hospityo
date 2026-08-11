<?php

use App\Models\Admission;
use App\Models\Bed;
use App\Models\Bill;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\IpdVisit;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    config(['visits.dual_write_enabled' => false]);

    $this->user = User::create([
        'name' => 'Admin Views User',
        'email' => 'admin-views@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('view visits', 'web');
    Permission::findOrCreate('edit visits', 'web');
    $this->user->givePermissionTo(['view visits', 'edit visits']);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Admin Views Patient',
        'gender' => 'male',
        'age' => 44,
        'phone' => '03005550001',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03005550002',
        'emergency_relation' => 'Sibling',
    ]);

    $department = Department::create(['name' => 'Medicine', 'code' => 'MED-ADM', 'status' => 'active']);

    $ward = Ward::create([
        'name' => 'Admin Ward',
        'department_id' => $department->id,
        'capacity' => 5,
        'ward_type' => 'general',
        'status' => 'active',
    ]);

    $this->bed = Bed::create([
        'ward_id' => $ward->id,
        'bed_number' => 'ADM-01',
        'bed_type' => 'general',
        'daily_rate' => 2500,
        'status' => 'available',
    ]);
});

function createAdmittedIpdVisit(Patient $patient, Bed $bed): Visit
{
    $visit = Visit::create([
        'patient_id' => $patient->id,
        'visit_type' => 'ipd',
        'visit_datetime' => now(),
        'status' => 'admitted',
        'doctor_id' => null,
    ]);

    IpdVisit::create(['visit_id' => $visit->id]);

    Admission::create([
        'visit_id' => $visit->id,
        'bed_id' => $bed->id,
        'admission_date' => now(),
        'status' => 'active',
    ]);

    $bed->update(['status' => 'occupied']);

    return $visit->fresh(['admission.bed.ward']);
}

it('show and edit blades contain no legacy spine column reads', function () {
    foreach (['show.blade.php', 'edit.blade.php'] as $file) {
        $content = file_get_contents(resource_path("views/admin/visits/{$file}"));

        expect($content)->not->toMatch('/\$visit->bed_no/')
            ->and($content)->not->toMatch('/\$visit->room_no/')
            ->and($content)->not->toMatch('/\$visit->total_charges/')
            ->and($content)->not->toMatch('/\$visit->chief_complaint/')
            ->and($content)->not->toMatch('/\$visit->diagnosis/')
            ->and($content)->not->toMatch('/\$visit->treatment/');
    }
});

it('show displays bed from admission not legacy spine bed_no', function () {
    $visit = createAdmittedIpdVisit($this->patient, $this->bed);

    $this->get(route('visits.show', $visit))
        ->assertOk()
        ->assertSee('ADM-01')
        ->assertSee('Admin Ward');
});

it('show displays charges from bills not legacy spine total_charges', function () {
    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'completed',
    ]);

    OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'medium']);

    Bill::create([
        'patient_id' => $this->patient->id,
        'visit_id' => $visit->id,
        'bill_number' => 'BILL-ADM-001',
        'bill_date' => now()->toDateString(),
        'bill_type' => 'opd',
        'subtotal' => 5000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 5000,
        'paid_amount' => 5000,
        'due_amount' => 0,
        'status' => 'paid',
        'created_by' => $this->user->id,
    ]);

    $this->get(route('visits.show', $visit))
        ->assertOk()
        ->assertSee(format_currency(5000));
});

it('show displays opd queue priority from child table', function () {
    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
    ]);

    OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'critical']);

    $this->get(route('visits.show', $visit))
        ->assertOk()
        ->assertSee('Critical Priority');
});

it('admin update persists clinical fields to consultation not spine', function () {
    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
        'doctor_id' => null,
    ]);

    OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'medium']);

    $department = Department::first();
    $doctor = \App\Models\Doctor::create([
        'name' => 'Dr. Admin Update',
        'doctor_no' => 'DOC-ADM-UPD',
        'department_id' => $department->id,
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005550003',
        'email' => 'dr-admin-upd@example.com',
        'gender' => 'male',
        'experience_years' => 4,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
    ]);

    $this->put(route('visits.update', $visit), [
        'patient_id' => $this->patient->id,
        'doctor_id' => $doctor->id,
        'visit_type' => 'opd',
        'visit_datetime' => now()->format('Y-m-d H:i'),
        'status' => 'registered',
        'priority' => 'high',
        'chief_complaint' => 'Persistent cough',
        'diagnosis' => 'Upper respiratory infection',
        'treatment' => 'Rest and fluids',
        'notes' => 'Follow up in one week',
    ])->assertRedirect(route('visits.index'));

    $consultation = Consultation::where('visit_id', $visit->id)->where('is_current', true)->first();

    expect($consultation)->not->toBeNull()
        ->and($consultation->chief_complaint)->toBe('Persistent cough')
        ->and($consultation->provisional_diagnosis)->toBe('Upper respiratory infection')
        ->and($consultation->treatment)->toBe('Rest and fluids')
        ->and($consultation->notes)->toBe('Follow up in one week')
        ->and(OpdVisit::where('visit_id', $visit->id)->value('queue_priority'))->toBe('high');
});
