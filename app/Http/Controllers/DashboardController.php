<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\InventoryTransaction;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $nearExpiryCount = 0;
        try {
            if ($user->hasAnyRole(['Super Admin', 'Hospital Administrator', 'Pharmacist'])) {
                $nearExpiryCount = InventoryTransaction::nearExpiry(6)->count();
            }
        } catch (\Throwable $e) {
            Log::warning('[Dashboard] Failed to load near-expiry count', ['error' => $e->getMessage()]);
        }

        if ($user->hasRole('Doctor')) {
            return $this->doctorDashboard($request, $user, $nearExpiryCount);
        }

        return view('admin.dashboard', compact('nearExpiryCount'));
    }

    protected function doctorDashboard(Request $request, $user, int $nearExpiryCount)
    {
        $doctor = Doctor::where('user_id', $user->id)->first();

        $fromDate = Carbon::parse($request->input('from_date', now()->startOfMonth()->format('Y-m-d')))->startOfDay();
        $toDate = Carbon::parse($request->input('to_date', now()->format('Y-m-d')))->endOfDay();

        if ($toDate->lt($fromDate)) {
            [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
        }

        $from = $fromDate->format('Y-m-d');
        $to = $toDate->format('Y-m-d');

        if (!$doctor) {
            return view('admin.dashboard', [
                'nearExpiryCount' => $nearExpiryCount,
                'isDoctorDashboard' => true,
                'assignedPatients' => collect(),
                'totalAssigned' => 0,
                'fromDate' => $from,
                'toDate' => $to,
                'stats' => [
                    'total_visits' => 0,
                    'opd_visits' => 0,
                    'ipd_visits' => 0,
                    'emergency_visits' => 0,
                    'active_visits' => 0,
                    'completed_visits' => 0,
                    'appointments' => 0,
                    'patients' => 0,
                ],
            ]);
        }

        $visitQuery = Visit::where('doctor_id', $doctor->id)
            ->whereBetween('visit_datetime', [$fromDate, $toDate]);

        $activeStatuses = ['registered', 'vitals_recorded', 'with_doctor', 'triaged'];

        $stats = [
            'total_visits' => (clone $visitQuery)->count(),
            'opd_visits' => (clone $visitQuery)->where('visit_type', 'opd')->count(),
            'ipd_visits' => (clone $visitQuery)->where('visit_type', 'ipd')->count(),
            'emergency_visits' => (clone $visitQuery)->where('visit_type', 'emergency')->count(),
            'active_visits' => (clone $visitQuery)->whereIn('status', $activeStatuses)->count(),
            'completed_visits' => (clone $visitQuery)->where('status', 'completed')->count(),
            'appointments' => Appointment::where('doctor_id', $doctor->id)
                ->whereBetween('appointment_datetime', [$fromDate, $toDate])
                ->count(),
            'patients' => (clone $visitQuery)->select('patient_id')->distinct()->count('patient_id'),
        ];

        $assignedPatients = $doctor->assignedPatients()
            ->whereBetween('visit_datetime', [$fromDate, $toDate])
            ->limit(5)
            ->get();

        $totalAssigned = $doctor->assignedPatients()
            ->whereBetween('visit_datetime', [$fromDate, $toDate])
            ->count();

        return view('admin.dashboard', [
            'nearExpiryCount' => $nearExpiryCount,
            'isDoctorDashboard' => true,
            'assignedPatients' => $assignedPatients,
            'totalAssigned' => $totalAssigned,
            'fromDate' => $from,
            'toDate' => $to,
            'stats' => $stats,
            'doctor' => $doctor,
        ]);
    }
}
