<?php

namespace App\Http\Controllers;

use App\Models\PreAnaesthesiaCheckup;
use App\Models\Surgery;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PacController extends Controller
{
    /**
     * List all PAC requests (pending + completed).
     */
    public function index(Request $request)
    {
        $query = PreAnaesthesiaCheckup::with(['surgery', 'patient', 'anaesthetist'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $checkups = $query->paginate(15)->withQueryString();

        return view('admin.ot.pac.index', compact('checkups'));
    }

    /**
     * Show the PAC request form for a specific surgery.
     */
    public function create(Surgery $surgery)
    {
        // Check if PAC already exists for this surgery
        if ($surgery->pacCheckup) {
            return redirect()->route('ot.pac.show', $surgery->pacCheckup)
                ->with('error', 'A PAC request already exists for this surgery.');
        }

        $anaesthetists = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Doctor', 'Hospital Administrator', 'Super Admin']);
        })->orderBy('name')->get();

        return view('admin.ot.pac.create', compact('surgery', 'anaesthetists'));
    }

    /**
     * Store a new PAC request.
     */
    public function store(Request $request, Surgery $surgery)
    {
        if ($surgery->pacCheckup) {
            return back()->with('error', 'A PAC request already exists for this surgery.');
        }

        $validated = $request->validate([
            'anaesthetist_id'          => 'nullable|exists:tenant.users,id',
            'asa_grade'                => 'nullable|in:ASA I,ASA II,ASA III,ASA IV,ASA V,ASA VI',
            'medical_history'          => 'nullable|string|max:3000',
            'current_medications'      => 'nullable|string|max:2000',
            'allergies'                => 'nullable|string|max:1000',
            'airway_assessment'        => 'nullable|string|max:1000',
            'mallampati_class'         => 'nullable|in:I,II,III,IV',
            'cardiovascular_status'    => 'nullable|string|max:1000',
            'respiratory_status'       => 'nullable|string|max:1000',
            'renal_hepatic_status'     => 'nullable|string|max:1000',
            'blood_pressure'           => 'nullable|string|max:20',
            'heart_rate'               => 'nullable|string|max:10',
            'spo2'                     => 'nullable|string|max:10',
            'weight_kg'                => 'nullable|string|max:10',
            'investigations_reviewed'  => 'nullable|string|max:2000',
            'proposed_anaesthesia_type'=> 'nullable|in:general,regional,local,sedation',
            'special_precautions'      => 'nullable|string|max:2000',
            'fasting_instructions'     => 'nullable|string|max:1000',
            'premedication'            => 'nullable|string|max:1000',
        ]);

        try {
            PreAnaesthesiaCheckup::create(array_merge($validated, [
                'surgery_id'   => $surgery->id,
                'patient_id'   => $surgery->patient_id,
                'requested_by' => auth()->id(),
                'status'       => 'pending',
            ]));
        } catch (\Throwable $e) {
            Log::error('[PAC] Failed to create PAC request', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to create PAC request.');
        }

        return redirect()->route('ot.surgeries.show', $surgery)
            ->with('success', 'PAC request submitted successfully.');
    }

    /**
     * Show a specific PAC record.
     */
    public function show(PreAnaesthesiaCheckup $pac)
    {
        $pac->load(['surgery.patient', 'surgery.doctor', 'anaesthetist', 'requestedBy']);
        return view('admin.ot.pac.show', compact('pac'));
    }

    /**
     * Clear (approve) a PAC — mark patient as fit for surgery.
     */
    public function clear(Request $request, PreAnaesthesiaCheckup $pac)
    {
        if ($pac->status !== 'pending') {
            return back()->with('error', 'This PAC has already been reviewed.');
        }

        $request->validate([
            'clearance_notes' => 'nullable|string|max:2000',
        ]);

        try {
            $pac->update([
                'status'           => 'cleared',
                'clearance_notes'  => $request->clearance_notes,
                'cleared_at'       => now(),
                'anaesthetist_id'  => $pac->anaesthetist_id ?? auth()->id(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[PAC] Failed to clear PAC', ['id' => $pac->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to clear PAC.');
        }

        return back()->with('success', 'Patient cleared for surgery.');
    }

    /**
     * Mark PAC as not cleared — patient not fit for surgery.
     */
    public function decline(Request $request, PreAnaesthesiaCheckup $pac)
    {
        if ($pac->status !== 'pending') {
            return back()->with('error', 'This PAC has already been reviewed.');
        }

        $request->validate([
            'clearance_notes' => 'required|string|max:2000',
        ]);

        try {
            $pac->update([
                'status'           => 'not_cleared',
                'clearance_notes'  => $request->clearance_notes,
                'anaesthetist_id'  => $pac->anaesthetist_id ?? auth()->id(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[PAC] Failed to decline PAC', ['id' => $pac->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to update PAC status.');
        }

        return back()->with('success', 'Patient marked as not cleared for surgery.');
    }

    /**
     * Mark PAC as requiring further evaluation.
     */
    public function requireFurtherEval(Request $request, PreAnaesthesiaCheckup $pac)
    {
        if ($pac->status !== 'pending') {
            return back()->with('error', 'This PAC has already been reviewed.');
        }

        $request->validate([
            'clearance_notes' => 'required|string|max:2000',
        ]);

        try {
            $pac->update([
                'status'           => 'requires_further_evaluation',
                'clearance_notes'  => $request->clearance_notes,
                'anaesthetist_id'  => $pac->anaesthetist_id ?? auth()->id(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[PAC] Failed to update PAC', ['id' => $pac->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to update PAC status.');
        }

        return back()->with('success', 'PAC marked as requiring further evaluation.');
    }
}
