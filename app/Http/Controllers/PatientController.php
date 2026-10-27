<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RedirectsAfterWrite;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use App\Traits\HandlesErrors;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class PatientController extends Controller
{
    use HandlesErrors, RedirectsAfterWrite;

    public function index(): View
    {
        return view('admin.patients.index');
    }

    public function data()
    {
        $query = Patient::query()
            ->orderByDesc('id'); // Latest first (newest patients)

        return DataTables::eloquent($query)
            ->toJson();
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

            $message = "Patient {$patient->name} created successfully with Patient No: {$patient->patient_no}";

            if ($request->expectsJson()) {
                return $this->successResponse($message, $patient, 'patients.index');
            }

            if ($request->has('save_and_add_another')) {
                return redirect()->route('patients.create')->with('success', $message);
            }

            return $this->redirectAfterWrite('patients.index', [], ['view patients'], $message, 'patients.create');
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

            $message = "Patient {$patient->name} updated successfully";

            if ($request->expectsJson()) {
                return $this->successResponse($message, $patient, 'patients.index');
            }

            return $this->redirectAfterWrite(
                'patients.index',
                [],
                ['view patients'],
                $message,
                'patients.edit',
                ['patient' => $patient],
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->handleException($e, 'Patient update');
        }
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $name = $patient->name;
        $patient->delete();

        Log::info('Patient moved to trash', [
            'patient_id' => $patient->id,
            'user_id' => auth()->id(),
        ]);

        return $this->redirectAfterWrite(
            'patients.index',
            [],
            ['view patients'],
            "Patient {$name} moved to trash.",
            null,
        );
    }

    public function trashed(): View
    {
        $patients = Patient::onlyTrashed()->orderByDesc('deleted_at')->paginate(20);

        return view('admin.patients.trashed', compact('patients'));
    }

    public function restore(int $patient): RedirectResponse
    {
        $model = Patient::onlyTrashed()->findOrFail($patient);
        $model->restore();

        Log::info('Patient restored from trash', [
            'patient_id' => $model->id,
            'user_id' => auth()->id(),
        ]);

        return $this->redirectAfterWrite(
            'patients.index',
            [],
            ['view patients'],
            "Patient {$model->name} restored.",
            'patients.trashed',
        );
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

    public function search(Request $request): JsonResponse
    {
        $phone = $request->query('phone');

        if (!$phone || strlen($phone) < 3) {
            return response()->json(['found' => false]);
        }

        $patient = Patient::where('phone', 'like', "%{$phone}%")->first();

        if ($patient) {
            return response()->json([
                'found' => true,
                'patient' => [
                    'id' => $patient->id,
                    'name' => $patient->name,
                    'patient_no' => $patient->patient_no,
                    'phone' => $patient->phone,
                ],
            ]);
        }

        return response()->json(['found' => false]);
    }
}