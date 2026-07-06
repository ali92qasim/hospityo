<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
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
            $doctor = Doctor::where('user_id', $user->id)->first();
            if ($doctor) {
                $assignedPatients = $doctor->assignedPatients()->limit(5)->get();
                $totalAssigned = $doctor->assignedPatients()->count();

                return view('admin.dashboard', compact('assignedPatients', 'totalAssigned', 'nearExpiryCount'));
            }
        }

        return view('admin.dashboard', compact('nearExpiryCount'));
    }
}
