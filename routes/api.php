<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Patient;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth')->group(function () {
    Route::get('/patients/search', function (Request $request) {
        $phone = $request->query('phone');
        
        if (!$phone) {
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
                    'phone' => $patient->phone
                ]
            ]);
        }
        
        return response()->json(['found' => false]);
    });
});