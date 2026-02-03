<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(): View
    {
        $patients = Patient::latest()->paginate(10);
        return view('admin.patients.index', compact('patients'));
    }

    public function create(): View
    {
        return view('admin.patients.create');
    }

    public function store(StorePatientRequest $request): RedirectResponse
    {
        Patient::create($request->validated());

        return redirect()->route('patients.index')
            ->with('success', 'Patient created successfully.');
    }

    public function show(Patient $patient): View
    {
        return view('admin.patients.show', compact('patient'));
    }

    public function edit(Patient $patient): View
    {
        return view('admin.patients.edit', compact('patient'));
    }

    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        $patient->update($request->validated());

        return redirect()->route('patients.index')
            ->with('success', 'Patient updated successfully.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $patient->delete();

        return redirect()->route('patients.index')
            ->with('success', 'Patient deleted successfully.');
    }

    public function history(Patient $patient): View
    {
        $visits = $patient->visits()->with(['doctor', 'consultation'])->latest()->get();
        $prescriptions = $patient->prescriptions()->with(['items.medicine'])->latest()->get();
        $labOrders = $patient->labOrders()->with(['labTest', 'result'])->latest()->get();
        $admissions = $patient->admissions()->with(['bed.ward'])->latest()->get();
        
        // Get latest visit with all related data
        $latestVisit = $patient->visits()
            ->with([
                'doctor', 
                'consultation', 
                'vitalSigns', 
                'prescriptions.items.medicine',
                'labOrders.labTest'
            ])
            ->latest()
            ->first();
        
        return view('admin.patients.history', compact('patient', 'visits', 'prescriptions', 'labOrders', 'admissions', 'latestVisit'));
    }
}