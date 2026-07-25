<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\IpdConsultantVisit;
use App\Models\PatientComplaint;
use App\Models\Visit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class IpdClinicalService
{
    public static function ensureIpdVisit(Visit $visit): void
    {
        if ($visit->visit_type !== 'ipd') {
            throw ValidationException::withMessages([
                'visit' => 'This action is only available for IPD visits.',
            ]);
        }
    }

    public static function authDoctor(): ?Doctor
    {
        $userId = Auth::id();

        if (! $userId) {
            return null;
        }

        return Doctor::where('user_id', $userId)->first();
    }

    public static function canManageConsultantVisit(IpdConsultantVisit $record): bool
    {
        $doctor = self::authDoctor();

        if (! $doctor) {
            return true;
        }

        return (int) $record->consultant_doctor_id === (int) $doctor->id;
    }

    public static function assertCanManageConsultantVisit(IpdConsultantVisit $record): void
    {
        if (! self::canManageConsultantVisit($record)) {
            throw ValidationException::withMessages([
                'consultant_visit' => 'You can only manage your own consultant visit records.',
            ]);
        }
    }

    public static function resolveConsultantDoctorId(?int $requestedDoctorId): int
    {
        $authDoctor = self::authDoctor();

        if ($authDoctor) {
            return (int) $authDoctor->id;
        }

        if (! $requestedDoctorId) {
            throw ValidationException::withMessages([
                'consultant_doctor_id' => 'Please select a consultant.',
            ]);
        }

        return (int) $requestedDoctorId;
    }

    public static function activeComplaintsForPatient(int $patientId)
    {
        return PatientComplaint::query()
            ->with(['recordedBy', 'visit'])
            ->where('patient_id', $patientId)
            ->where('status', 'active')
            ->latest()
            ->get();
    }

    public static function syncComplaintFromConsultation(Visit $visit, ?string $presentingComplaints): void
    {
        if ($visit->visit_type !== 'ipd' || blank($presentingComplaints)) {
            return;
        }

        $lines = collect(preg_split('/\R/', trim($presentingComplaints)))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();

        if ($lines->isEmpty()) {
            return;
        }

        foreach ($lines as $line) {
            $exists = PatientComplaint::query()
                ->where('patient_id', $visit->patient_id)
                ->where('visit_id', $visit->id)
                ->where('status', 'active')
                ->where('complaint', $line)
                ->exists();

            if ($exists) {
                continue;
            }

            PatientComplaint::create([
                'patient_id'   => $visit->patient_id,
                'visit_id'     => $visit->id,
                'complaint'    => $line,
                'status'       => 'active',
                'recorded_by'  => Auth::id(),
            ]);
        }
    }
}
