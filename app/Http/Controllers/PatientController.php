<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index()
    {
        $patients = Patient::latest()->paginate(10);
        return view('admin.patients.index', compact('patients'));
    }

    public function create()
    {
        return view('admin.patients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'age' => 'required|integer|min:1|max:150',
            'phone' => 'required|string|max:20',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'present_address' => 'nullable|string',
            'permanent_address' => 'nullable|string',
            'emergency_name' => 'required|string|max:255',
            'emergency_phone' => 'required|string|max:20',
            'emergency_relation' => 'required|string|max:100',
        ]);

        Patient::create($validated);

        return redirect()->route('patients.index')
            ->with('success', 'Patient created successfully.');
    }

    public function show(Patient $patient)
    {
        return view('admin.patients.show', compact('patient'));
    }

    public function edit(Patient $patient)
    {
        return view('admin.patients.edit', compact('patient'));
    }

    public function update(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'age' => 'required|integer|min:1|max:150',
            'phone' => 'required|string|max:20',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'present_address' => 'nullable|string',
            'permanent_address' => 'nullable|string',
            'emergency_name' => 'required|string|max:255',
            'emergency_phone' => 'required|string|max:20',
            'emergency_relation' => 'required|string|max:100',
        ]);

        $patient->update($validated);

        return redirect()->route('patients.index')
            ->with('success', 'Patient updated successfully.');
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();

        return redirect()->route('patients.index')
            ->with('success', 'Patient deleted successfully.');
    }

    public function history(Patient $patient)
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