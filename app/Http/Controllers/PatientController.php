<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use App\Traits\HandlesErrors;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PatientController extends Controller
{
    use HandlesErrors;

    public function index(): View
    {
        try {
            $search = request('search');
            
            $patients = Patient::query()
                ->when($search, function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('patient_no', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
                ->latest()
                ->paginate(10);
            
            return view('admin.patients.index', compact('patients'));
        } catch (\Throwable $e) {
            Log::error('Failed to load patients', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            
            // Return view with empty collection and error message
            return view('admin.patients.index', [
                'patients' => Patient::paginate(0)
            ])->with('error', 'Failed to load patients. Please try again.');
        }
    }

    public function create(): View
    {
        return view('admin.patients.create');
    }

    public function store(StorePatientRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();
            
            $patient = Patient::create($request->validated());
            
            DB::commit();
            
            Log::info('Patient created successfully', [
                'patient_id' => $patient->id,
                'patient_no' => $patient->patient_no,
                'user_id' => auth()->id(),
            ]);

            return $this->successResponse(
                "Patient {$patient->name} created successfully with Patient No: {$patient->patient_no}",
                $patient,
                'patients.index'
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->handleException($e, 'Patient creation');
        }
    }

    public function show(Patient $patient): View
    {
        try {
            $patient->load(['visits', 'prescriptions', 'labOrders']);
            return view('admin.patients.show', compact('patient'));
        } catch (\Throwable $e) {
            Log::error('Failed to load patient details', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);
            
            return view('admin.patients.show', compact('patient'))
                ->with('error', 'Some patient data could not be loaded.');
        }
    }

    public function edit(Patient $patient): View
    {
        return view('admin.patients.edit', compact('patient'));
    }

    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        try {
            DB::beginTransaction();
            
            $patient->update($request->validated());
            
            DB::commit();
            
            Log::info('Patient updated successfully', [
                'patient_id' => $patient->id,
                'patient_no' => $patient->patient_no,
                'user_id' => auth()->id(),
            ]);

            return $this->successResponse(
                "Patient {$patient->name} updated successfully",
                $patient,
                'patients.index'
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->handleException($e, 'Patient update');
        }
    }

    public function history(Patient $patient): View
    {
        $visits = $patient->visits()->with(['doctor', 'consultation'])->latest()->get();
        $prescriptions = $patient->prescriptions()->with(['items.medicine'])->latest()->get();
        $labOrders = $patient->labOrders()->with(['investigation', 'result'])->latest()->get();
        $admissions = $patient->admissions()->with(['bed.ward'])->latest()->get();
        
        // Get latest visit with all related data
        $latestVisit = $patient->visits()
            ->with([
                'doctor', 
                'consultation', 
                'vitalSigns', 
                'prescriptions.items.medicine',
                'labOrders.investigation'
            ])
            ->latest()
            ->first();
        
        return view('admin.patients.history', compact('patient', 'visits', 'prescriptions', 'labOrders', 'admissions', 'latestVisit'));
    }
}