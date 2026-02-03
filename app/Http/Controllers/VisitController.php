<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVisitRequest;
use App\Http\Requests\UpdateVisitRequest;
use App\Http\Requests\UpdateVitalsRequest;
use App\Http\Requests\AssignDoctorRequest;
use App\Http\Requests\UpdateConsultationRequest;
use App\Http\Requests\AddTestOrdersRequest;
use App\Http\Requests\UpdateTestResultRequest;
use App\Http\Requests\AdmitPatientRequest;
use App\Http\Requests\DischargePatientRequest;
use App\Http\Requests\TriagePatientRequest;
use App\Http\Requests\CreatePrescriptionRequest;
use App\Http\Requests\OrderLabTestRequest;
use App\Models\Visit;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\LabTest;
use App\Models\LabOrder;
use App\Models\VitalSign;
use App\Models\Consultation;
use App\Models\TestOrder;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\Admission;
use App\Models\Triage;
use App\Models\Notification;
use App\Models\Medicine;
use App\Models\Prescription;
use App\Events\PatientAssigned;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function index(Request $request)
    {
        $query = Visit::with(['patient', 'doctor']);

        // If user is a doctor, only show visits assigned to them
        if (auth()->user()->hasRole('Doctor')) {
            $doctor = Doctor::where('user_id', auth()->id())->first();
            if ($doctor) {
                $query->where('doctor_id', $doctor->id);
            } else {
                $query->whereNull('id'); // No visits if doctor record not found
            }
        }

        // Search functionality
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('visit_no', 'like', "%{$search}%")
                  ->orWhereHas('patient', function($patientQuery) use ($search) {
                      $patientQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('patient_no', 'like', "%{$search}%")
                                   ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('doctor', function($doctorQuery) use ($search) {
                      $doctorQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->visit_type) {
            $query->where('visit_type', $request->visit_type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $visits = $query->latest()->paginate(10)->withQueryString();
        return view('admin.visits.index', compact('visits'));
    }

    public function create()
    {
        $patients = Patient::all();
        return view('admin.visits.create', compact('patients'));
    }

    public function store(StoreVisitRequest $request)
    {
        $visit = Visit::create($request->validated());

        return redirect()->route('visits.workflow', $visit)
            ->with('success', 'Visit registered successfully. Please record vital signs.');
    }

    public function show(Visit $visit)
    {
        $visit->load(['patient', 'doctor.department', 'vitalSigns', 'consultation', 'testOrders']);
        return view('admin.visits.show', compact('visit'));
    }

    public function edit(Visit $visit)
    {
        $patients = Patient::all();
        $doctors = Doctor::where('status', 'active')->with('department')->get();
        return view('admin.visits.edit', compact('visit', 'patients', 'doctors'));
    }

    public function update(UpdateVisitRequest $request, Visit $visit)
    {
        $visit->update($request->validated());

        return redirect()->route('visits.index')
            ->with('success', 'Visit updated successfully.');
    }

    public function workflow(Visit $visit)
    {
        $visit->load(['patient', 'doctor.department', 'vitalSigns', 'allVitalSigns.user', 'consultation', 'testOrders', 'labOrders.labTest', 'labOrders.result', 'admission.bed.ward', 'triage', 'prescriptions.items.medicine']);
        $doctors = Doctor::where('status', 'active')->get();
        $medicines = Medicine::where('status', 'active')
            ->get()
            ->filter(function($medicine) {
                return $medicine->getCurrentStock() > 0;
            });
        $labTests = LabTest::where('is_active', true)->get();

        $data = compact('visit', 'doctors', 'medicines', 'labTests');

        // Add type-specific data
        if ($visit->visit_type === 'ipd') {
            $data['availableBeds'] = Bed::with('ward')->where('status', 'available')->get();
        }

        return view('admin.visits.workflow', $data);
    }

    public function updateVitals(UpdateVitalsRequest $request, Visit $visit)
    {
        $validated = $request->validated();

        // For IPD patients, create new vital signs record each time
        if ($visit->visit_type === 'ipd') {
            $visit->allVitalSigns()->create([
                ...$validated,
                'recorded_by' => auth()->id()
            ]);
        } else {
            // For OPD/Emergency, update or create single record
            $visit->vitalSigns()->updateOrCreate(
                ['visit_id' => $visit->id],
                [...$validated, 'recorded_by' => auth()->id()]
            );
        }

        $visit->update(['status' => 'vitals_recorded']);

        return back()->with('success', 'Vital signs recorded successfully.');
    }

    public function assignDoctor(AssignDoctorRequest $request, Visit $visit)
    {
        $doctor = Doctor::findOrFail($request->doctor_id);

        $visit->update([
            'doctor_id' => $request->doctor_id,
            'status' => 'with_doctor'
        ]);

        // Create notification for doctor
        if ($doctor->user) {
            Notification::create([
                'user_id' => $doctor->user->id,
                'title' => 'New Patient Assignment',
                'message' => "Patient {$visit->patient->name} has been assigned to you for {$visit->visit_type} visit.",
                'type' => 'patient_assignment',
                'data' => [
                    'visit_id' => $visit->id,
                    'patient_name' => $visit->patient->name,
                    'visit_type' => $visit->visit_type
                ]
            ]);

            // Broadcast live notification
            broadcast(new PatientAssigned($visit, $doctor->user->id));

            // Debug: Log the broadcast
            \Log::info('Broadcasting PatientAssigned event', [
                'visit_id' => $visit->id,
                'doctor_user_id' => $doctor->user->id,
                'patient_name' => $visit->patient->name
            ]);
        }

        return back()->with('success', 'Doctor assigned successfully.');
    }

    public function updateConsultation(UpdateConsultationRequest $request, Visit $visit)
    {
        $visit->consultation()->updateOrCreate(
            ['visit_id' => $visit->id],
            $request->validated()
        );

        return back()->with('success', 'Consultation updated successfully.');
    }

    public function addTestOrders(AddTestOrdersRequest $request, Visit $visit)
    {
        $validated = $request->validated();

        foreach ($validated['tests'] as $testData) {
            $visit->testOrders()->create([
                ...$testData,
                'ordered_at' => now(),
            ]);
        }

        $testCount = count($validated['tests']);
        $message = $testCount === 1 ? 'Test added successfully.' : "{$testCount} tests added successfully.";
        
        return back()->with('success', $message);
    }

    public function removeTestOrder(TestOrder $testOrder)
    {
        $testOrder->delete();
        return back()->with('success', 'Test removed successfully.');
    }

    public function updateTestResult(UpdateTestResultRequest $request, TestOrder $testOrder)
    {
        $testOrder->update([
            'results' => $request->results,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Check if all tests are completed
        $visit = $testOrder->visit;
        $allTestsCompleted = $visit->testOrders()->where('status', 'ordered')->count() === 0;

        if ($allTestsCompleted) {
            $visit->update(['status' => 'tests_completed']);
        }

        return back()->with('success', 'Test result updated successfully.');
    }

    public function completeVisit(Visit $visit)
    {
        $visit->update(['status' => 'completed']);
        return redirect()->route('visits.index')->with('success', 'Visit completed successfully.');
    }

    // IPD Methods
    public function admitPatient(AdmitPatientRequest $request, Visit $visit)
    {
        $bed = Bed::findOrFail($request->bed_id);
        $bed->update(['status' => 'occupied']);

        $visit->admission()->create([
            'bed_id' => $request->bed_id,
            'admission_date' => now(),
            'admission_notes' => $request->admission_notes
        ]);

        $visit->update(['status' => 'admitted']);

        return back()->with('success', 'Patient admitted successfully.');
    }

    public function dischargePatient(DischargePatientRequest $request, Visit $visit)
    {
        $admission = $visit->admission;
        $admission->update([
            'discharge_date' => now(),
            'status' => 'discharged',
            'discharge_notes' => $request->discharge_notes,
            'discharge_summary' => $request->discharge_summary
        ]);

        $admission->bed->update(['status' => 'available']);
        $visit->update(['status' => 'discharged']);

        return back()->with('success', 'Patient discharged successfully.');
    }

    // Emergency Methods
    public function triagePatient(TriagePatientRequest $request, Visit $visit)
    {
        $visit->triage()->create([
            ...$request->only(['priority_level', 'chief_complaint', 'pain_scale', 'triage_notes']),
            'triaged_by' => auth()->id(),
            'triaged_at' => now()
        ]);

        $visit->update(['status' => 'triaged']);

        return back()->with('success', 'Patient triaged successfully.');
    }

    public function createPrescription(CreatePrescriptionRequest $request, Visit $visit)
    {
        $prescription = $visit->prescriptions()->create([
            'patient_id' => $visit->patient_id,
            'doctor_id' => $visit->doctor_id,
            'notes' => $request->notes,
            'status' => 'pending'
        ]);

        foreach ($request->medicines as $medicineData) {
            $medicine = Medicine::find($medicineData['medicine_id']);

            // Check stock availability
            if ($medicine->stock_quantity < $medicineData['quantity']) {
                return back()->withErrors([
                    'stock' => "Insufficient stock for {$medicine->name}. Available: {$medicine->stock_quantity}"
                ]);
            }

            $prescription->items()->create([
                'medicine_id' => $medicineData['medicine_id'],
                'quantity' => $medicineData['quantity'],
                'dosage' => $medicineData['dosage'],
                'instructions' => $medicineData['instructions'] ?? null
            ]);
        }

        return back()->with('success', 'Prescription created successfully.');
    }

    public function orderLabTest(OrderLabTestRequest $request, Visit $visit)
    {
        $validated = $request->validated();

        $visit->labOrders()->create([
            'patient_id' => $visit->patient_id,
            'doctor_id' => $visit->doctor_id,
            'lab_test_id' => $validated['lab_test_id'],
            'test_location' => $validated['test_location'],
            'priority' => $validated['priority'],
            'clinical_notes' => $validated['clinical_notes'],
            'status' => 'ordered',
            'ordered_at' => now()
        ]);
        
        return back()->with('success', 'Lab test ordered successfully.');
    }
}
