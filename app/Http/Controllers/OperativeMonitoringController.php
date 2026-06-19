<?php

namespace App\Http\Controllers;

use App\Models\AnaesthesiaRecord;
use App\Models\OperativeVital;
use App\Models\PostOpMonitoring;
use App\Models\Surgery;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OperativeMonitoringController extends Controller
{
    // ── Anaesthesia Record ─────────────────────────────────────────────────────

    public function anaesthesiaForm(Surgery $surgery)
    {
        try {
            $surgery->load(['patient', 'doctor', 'anaesthesiaRecord']);
            $anaesthetists = User::whereHas('roles', fn($q) => $q->whereIn('name', ['Doctor', 'Hospital Administrator', 'Super Admin']))
                ->orderBy('name')->get();

            return view('admin.ot.monitoring.anaesthesia', compact('surgery', 'anaesthetists'));
        } catch (\Throwable $e) {
            Log::error('[Monitoring] Anaesthesia form failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load anaesthesia form.');
        }
    }

    public function storeAnaesthesia(Request $request, Surgery $surgery)
    {
        $validated = $request->validate([
            'anaesthetist_id'         => 'required|exists:tenant.users,id',
            'anaesthesia_type'        => 'required|in:general,regional,local,sedation,combined',
            'airway_management'       => 'nullable|in:ETT,LMA,facemask,tracheostomy',
            'ett_size'                => 'nullable|string|max:10',
            'induction_agent'         => 'nullable|string|max:255',
            'induction_dose'          => 'nullable|string|max:100',
            'maintenance_agent'       => 'nullable|string|max:255',
            'muscle_relaxant'         => 'nullable|string|max:255',
            'reversal_agent'          => 'nullable|string|max:255',
            'regional_technique'      => 'nullable|string|max:1000',
            'iv_fluids'               => 'nullable|string|max:1000',
            'estimated_blood_loss_ml' => 'nullable|integer|min:0',
            'urine_output_ml'         => 'nullable|integer|min:0',
            'intra_op_medications'    => 'nullable|string|max:2000',
            'intra_op_events'         => 'nullable|string|max:2000',
            'induction_time'          => 'nullable|date',
            'intubation_time'         => 'nullable|date',
            'extubation_time'         => 'nullable|date',
            'recovery_status'         => 'nullable|in:awake,drowsy,intubated',
            'post_op_instructions'    => 'nullable|string|max:2000',
            'pain_management_plan'    => 'nullable|string|max:2000',
        ]);

        try {
            $record = $surgery->anaesthesiaRecord;
            if ($record) {
                $record->update($validated);
            } else {
                AnaesthesiaRecord::create(array_merge($validated, [
                    'surgery_id' => $surgery->id,
                ]));
            }
        } catch (\Throwable $e) {
            Log::error('[Monitoring] Store anaesthesia failed', ['surgery_id' => $surgery->id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to save anaesthesia record.');
        }

        return redirect()->route('ot.surgeries.show', $surgery)->with('success', 'Anaesthesia record saved.');
    }

    // ── Intra-Operative Vitals ────────────────────────────────────────────────

    public function vitalsForm(Surgery $surgery)
    {
        try {
            $surgery->load(['patient', 'operativeVitals.recordedByUser']);
            return view('admin.ot.monitoring.vitals', compact('surgery'));
        } catch (\Throwable $e) {
            Log::error('[Monitoring] Vitals form failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load vitals form.');
        }
    }

    public function storeVitals(Request $request, Surgery $surgery)
    {
        $validated = $request->validate([
            'recorded_at'               => 'required|date',
            'blood_pressure_systolic'   => 'nullable|string|max:10',
            'blood_pressure_diastolic'  => 'nullable|string|max:10',
            'heart_rate'                => 'nullable|string|max:10',
            'spo2'                      => 'nullable|string|max:10',
            'etco2'                     => 'nullable|string|max:10',
            'respiratory_rate'          => 'nullable|string|max:10',
            'temperature'              => 'nullable|string|max:10',
            'mac_value'                 => 'nullable|string|max:10',
            'fio2'                      => 'nullable|string|max:10',
            'notes'                     => 'nullable|string|max:500',
        ]);

        try {
            OperativeVital::create(array_merge($validated, [
                'surgery_id'  => $surgery->id,
                'recorded_by' => auth()->id(),
            ]));
        } catch (\Throwable $e) {
            Log::error('[Monitoring] Store vitals failed', ['surgery_id' => $surgery->id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to record vitals.');
        }

        return back()->with('success', 'Vitals recorded.');
    }

    /**
     * AJAX endpoint to fetch vitals as JSON (for chart rendering).
     */
    public function vitalsData(Surgery $surgery)
    {
        try {
            $vitals = $surgery->operativeVitals()
                ->orderBy('recorded_at')
                ->get()
                ->map(fn($v) => [
                    'time'      => $v->recorded_at?->format('H:i'),
                    'systolic'  => $v->blood_pressure_systolic,
                    'diastolic' => $v->blood_pressure_diastolic,
                    'hr'        => $v->heart_rate,
                    'spo2'      => $v->spo2,
                    'etco2'     => $v->etco2,
                    'rr'        => $v->respiratory_rate,
                    'temp'      => $v->temperature,
                ]);

            return response()->json($vitals);
        } catch (\Throwable $e) {
            Log::error('[Monitoring] Vitals data failed', ['error' => $e->getMessage()]);
            return response()->json([], 500);
        }
    }

    // ── Post-Operative Monitoring ─────────────────────────────────────────────

    public function postOpForm(Surgery $surgery)
    {
        try {
            $surgery->load(['patient', 'postOpMonitoring.recordedByUser']);
            return view('admin.ot.monitoring.post-op', compact('surgery'));
        } catch (\Throwable $e) {
            Log::error('[Monitoring] Post-op form failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load post-op form.');
        }
    }

    public function storePostOp(Request $request, Surgery $surgery)
    {
        $validated = $request->validate([
            'recorded_at'         => 'required|date',
            'phase'               => 'required|in:pacu,ward',
            'consciousness_level' => 'nullable|in:alert,verbal,pain,unresponsive',
            'blood_pressure'      => 'nullable|string|max:20',
            'heart_rate'          => 'nullable|string|max:10',
            'spo2'                => 'nullable|string|max:10',
            'respiratory_rate'    => 'nullable|string|max:10',
            'temperature'         => 'nullable|string|max:10',
            'pain_score'          => 'nullable|string|max:5',
            'nausea_vomiting'     => 'nullable|in:none,mild,moderate,severe',
            'wound_status'        => 'nullable|string|max:500',
            'drain_output'        => 'nullable|string|max:500',
            'iv_fluids_given'     => 'nullable|string|max:500',
            'medications_given'   => 'nullable|string|max:1000',
            'notes'               => 'nullable|string|max:1000',
        ]);

        try {
            PostOpMonitoring::create(array_merge($validated, [
                'surgery_id'  => $surgery->id,
                'recorded_by' => auth()->id(),
            ]));
        } catch (\Throwable $e) {
            Log::error('[Monitoring] Store post-op failed', ['surgery_id' => $surgery->id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to record post-op entry.');
        }

        return back()->with('success', 'Post-operative entry recorded.');
    }
}
