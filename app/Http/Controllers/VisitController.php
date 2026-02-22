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
use App\Http\Requests\OrderMultipleLabTestsRequest;
use App\Models\Visit;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\Investigation;
use App\Models\LabOrder;
use App\Models\VitalSign;
use App\Models\Consultation;
use App\Models\TestOrder;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\Admission;
use App\Models\Triage;
use App\Models\Medicine;
use App\Models\Prescription;
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

        // Date filters
        if ($request->date_filter) {
            switch ($request->date_filter) {
                case 'today':
                    $query->whereDate('visit_datetime', today());
                    break;
                case 'yesterday':
                    $query->whereDate('visit_datetime', today()->subDay());
                    break;
                case 'this_week':
                    $query->whereBetween('visit_datetime', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ]);
                    break;
                case 'last_week':
                    $query->whereBetween('visit_datetime', [
                        now()->subWeek()->startOfWeek(),
                        now()->subWeek()->endOfWeek()
                    ]);
                    break;
                case 'this_month':
                    $query->whereMonth('visit_datetime', now()->month)
                          ->whereYear('visit_datetime', now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('visit_datetime', now()->subMonth()->month)
                          ->whereYear('visit_datetime', now()->subMonth()->year);
                    break;
            }
        }

        // Custom date range
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('visit_datetime', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
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
        $visit->load(['patient', 'doctor.department', 'vitalSigns', 'allVitalSigns.user', 'consultation.allergies', 'testOrders', 'labOrders.labTest', 'labOrders.result', 'admission.bed.ward', 'triage', 'prescriptions.items.medicine']);
        $doctors = Doctor::where('status', 'active')->get();
        $medicines = Medicine::where('status', 'active')
            ->get()
            ->filter(function($medicine) {
                return $medicine->getCurrentStock() > 0;
            });
        $investigations = Investigation::where('is_active', true)->orderBy('type')->orderBy('name')->get();
        $allergies = \App\Models\Allergy::orderBy('category')->orderBy('name')->get();

        $data = compact('visit', 'doctors', 'medicines', 'investigations', 'allergies');

        // Add type-specific data
        if ($visit->visit_type === 'ipd') {
            $data['availableBeds'] = Bed::with('ward')->where('status', 'available')->get();
        }

        return view('admin.visits.workflow', $data);
    }

    public function updateVitals(UpdateVitalsRequest $request, Visit $visit)
    {
        $validated = $request->validated();
        
        // Check if at least one vital sign field is filled
        $vitalFields = ['blood_pressure', 'temperature', 'pulse_rate', 'spo2', 'bsr', 'weight', 'height'];
        $hasAnyValue = false;
        
        foreach ($vitalFields as $field) {
            if (!empty($validated[$field])) {
                $hasAnyValue = true;
                break;
            }
        }
        
        if (!$hasAnyValue) {
            return back()->with('warning', 'Please fill in at least one vital sign measurement before saving. All fields are currently empty.');
        }

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

        return back()->with('success', 'Doctor assigned successfully.');
    }

    public function updateConsultation(UpdateConsultationRequest $request, Visit $visit)
    {
        $validated = $request->validated();
        
        // Extract allergies from validated data
        $allergyNames = $validated['allergies'] ?? [];
        unset($validated['allergies']);
        
        // Update or create consultation
        $consultation = $visit->consultation()->updateOrCreate(
            ['visit_id' => $visit->id],
            $validated
        );
        
        // Handle allergies
        if (!empty($allergyNames)) {
            $allergyIds = [];
            
            foreach ($allergyNames as $allergyName) {
                if (!empty($allergyName)) {
                    // Find existing allergy or create new one
                    $allergy = \App\Models\Allergy::firstOrCreate(
                        ['name' => $allergyName],
                        ['category' => 'other', 'is_standard' => false]
                    );
                    $allergyIds[] = $allergy->id;
                }
            }
            
            // Sync allergies (this will add new ones and remove unselected ones)
            $consultation->allergies()->sync($allergyIds);
        } else {
            // If no allergies selected, remove all
            $consultation->allergies()->sync([]);
        }

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

    public function checkPatient(Visit $visit)
    {
        // Only allow doctors to check their assigned patients
        if (!auth()->user()->hasRole('Doctor')) {
            abort(403);
        }
        
        $doctor = Doctor::where('user_id', auth()->id())->first();
        if (!$doctor || $visit->doctor_id !== $doctor->id) {
            abort(403);
        }
        
        $visit->update(['status' => 'completed']);
        return redirect()->back()->with('success', 'Patient checked successfully.');
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
        try {
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
        } catch (\Exception $e) {
            \Log::error('Failed to discharge patient: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to discharge patient. Please try again.']);
        }
    }

    // Emergency Methods
    public function triagePatient(TriagePatientRequest $request, Visit $visit)
    {
        try {
            $visit->triage()->create([
                ...$request->only(['priority_level', 'chief_complaint', 'pain_scale', 'triage_notes']),
                'triaged_by' => auth()->id(),
                'triaged_at' => now()
            ]);

            $visit->update(['status' => 'triaged']);

            return back()->with('success', 'Patient triaged successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to triage patient: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to triage patient. Please try again.']);
        }
    }

    public function createPrescription(CreatePrescriptionRequest $request, Visit $visit)
    {
        try {
            $prescription = $visit->prescriptions()->create([
                'patient_id' => $visit->patient_id,
                'doctor_id' => $visit->doctor_id,
                'prescribed_date' => now(),
                'notes' => $request->notes,
                'status' => 'pending'
            ]);

            foreach ($request->medicines as $medicineData) {
                $medicine = Medicine::find($medicineData['medicine_id']);

                // Check stock availability
                $currentStock = $medicine->getCurrentStock();
                if ($currentStock < $medicineData['quantity']) {
                    return back()->withErrors([
                        'stock' => "Insufficient stock for {$medicine->name}. Available: {$currentStock}"
                    ]);
                }

                // Get unit price from latest inventory transaction or default to 0
                $latestTransaction = $medicine->inventoryTransactions()
                    ->where('type', 'stock_in')
                    ->latest()
                    ->first();
                
                $unitPrice = $latestTransaction ? $latestTransaction->unit_cost : 0;
                $totalPrice = $unitPrice * $medicineData['quantity'];

                $prescription->items()->create([
                    'medicine_id' => $medicineData['medicine_id'],
                    'quantity' => $medicineData['quantity'],
                    'dosage' => $medicineData['dosage'],
                    'frequency' => 'As directed', // Default value
                    'duration' => 'As prescribed', // Default value
                    'instructions' => $medicineData['instructions'] ?? null,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice
                ]);
            }

            return back()->with('success', 'Prescription created successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create prescription. Please try again. Error: ' . $e->getMessage()]);
        }
    }

    public function orderMultipleLabTests(OrderMultipleLabTestsRequest $request, Visit $visit)
    {
        try {
            $validated = $request->validated();
            $orderedCount = 0;

            foreach ($validated['tests'] as $testData) {
                $visit->labOrders()->create([
                    'patient_id' => $visit->patient_id,
                    'doctor_id' => $visit->doctor_id,
                    'lab_test_id' => $testData['lab_test_id'],
                    'quantity' => $testData['quantity'],
                    'test_location' => 'indoor', // Default to indoor since field removed
                    'priority' => $testData['priority'],
                    'clinical_notes' => $testData['clinical_notes'] ?? null,
                    'status' => 'ordered',
                    'ordered_at' => now()
                ]);
                $orderedCount++;
            }
            
            $message = $orderedCount === 1 ? 'Lab test ordered successfully.' : "{$orderedCount} lab tests ordered successfully.";
            return back()->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Failed to order lab tests: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to order lab tests. Please try again.']);
        }
    }

    public function print(Visit $visit)
    {
        $visit->load([
            'patient',
            'doctor.department',
            'vitalSigns',
            'allVitalSigns.user',
            'consultation',
            'labOrders.labTest',
            'labOrders.result.resultItems',
            'admission.bed.ward',
            'triage',
            'prescriptions.items.medicine'
        ]);

        // Get hospital settings
        $settings = [
            'hospital_name' => cache('settings.hospital_name', config('app.name', 'Hospital Management System')),
            'hospital_address' => cache('settings.hospital_address', ''),
            'hospital_phone' => cache('settings.hospital_phone', ''),
            'hospital_email' => cache('settings.hospital_email', ''),
            'hospital_logo' => cache('settings.hospital_logo', null)
        ];

        return view('admin.visits.print', compact('visit', 'settings'));
    }
}