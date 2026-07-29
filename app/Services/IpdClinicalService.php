<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\IpdCareTeam;
use App\Models\IpdDoctorVisitNote;
use App\Models\PatientComplaint;
use App\Models\Visit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public static function careTeamDoctorIds(Visit $visit): array
    {
        $visit->loadMissing('careTeam');

        return $visit->careTeam->pluck('doctor_id')->map(fn ($id) => (int) $id)->all();
    }

    public static function isOnCareTeam(Visit $visit, int $doctorId): bool
    {
        return in_array($doctorId, self::careTeamDoctorIds($visit), true);
    }

    public static function addDoctorToCareTeam(Visit $visit, Doctor $doctor, ?int $addedBy): IpdCareTeam
    {
        self::ensureIpdVisit($visit);

        if (self::isOnCareTeam($visit, (int) $doctor->id)) {
            throw ValidationException::withMessages([
                'doctor_id' => 'This doctor is already an active member of the care team for this visit.',
            ]);
        }

        try {
            return DB::connection('tenant')->transaction(function () use ($visit, $doctor, $addedBy) {
                $activeCount = IpdCareTeam::query()
                    ->where('visit_id', $visit->id)
                    ->active()
                    ->lockForUpdate()
                    ->count();

                return IpdCareTeam::create([
                    'visit_id'   => $visit->id,
                    'doctor_id'  => $doctor->id,
                    'is_primary' => $activeCount === 0,
                    'added_at'   => now(),
                    'added_by'   => $addedBy,
                ]);
            });
        } catch (QueryException $e) {
            if (self::isUniqueConstraintViolation($e)) {
                throw ValidationException::withMessages([
                    'doctor_id' => 'This doctor is already an active member of the care team for this visit.',
                ]);
            }

            throw $e;
        }
    }

    public static function removeDoctorFromCareTeam(Visit $visit, Doctor $doctor): void
    {
        self::ensureIpdVisit($visit);

        $member = IpdCareTeam::query()
            ->where('visit_id', $visit->id)
            ->where('doctor_id', $doctor->id)
            ->active()
            ->first();

        if (! $member) {
            throw ValidationException::withMessages([
                'doctor_id' => 'This doctor is not an active member of the care team for this visit.',
            ]);
        }

        $member->update(['removed_at' => now()]);
    }

    public static function setPrimaryDoctor(Visit $visit, Doctor $doctor): void
    {
        self::ensureIpdVisit($visit);

        DB::connection('tenant')->transaction(function () use ($visit, $doctor) {
            $target = IpdCareTeam::query()
                ->where('visit_id', $visit->id)
                ->where('doctor_id', $doctor->id)
                ->active()
                ->lockForUpdate()
                ->first();

            if (! $target) {
                throw ValidationException::withMessages([
                    'doctor_id' => 'The selected doctor must be an active care team member before being set as primary.',
                ]);
            }

            if ($target->is_primary) {
                return;
            }

            IpdCareTeam::query()
                ->where('visit_id', $visit->id)
                ->active()
                ->primary()
                ->lockForUpdate()
                ->update(['is_primary' => false]);

            $target->update(['is_primary' => true]);
        });
    }

    public static function addDoctorVisitNote(
        Visit $visit,
        Doctor $doctor,
        ?string $notes,
        ?int $createdBy
    ): IpdDoctorVisitNote {
        self::ensureIpdVisit($visit);

        if (! self::isOnCareTeam($visit, (int) $doctor->id)) {
            throw ValidationException::withMessages([
                'doctor_id' => 'Visit notes can only be recorded by an active care team member.',
            ]);
        }

        if (blank($notes)) {
            throw ValidationException::withMessages([
                'notes' => 'Enter visit notes.',
            ]);
        }

        return $visit->doctorVisitNotes()->create([
            'doctor_id'  => $doctor->id,
            'notes'      => $notes,
            'orders'     => null,
            'status'     => 'pending',
            'visited_at' => now(),
            'created_by' => $createdBy,
        ]);
    }

    public static function canManageDoctorVisitNote(IpdDoctorVisitNote $record): bool
    {
        $doctor = self::authDoctor();

        if (! $doctor) {
            return true;
        }

        return (int) $record->doctor_id === (int) $doctor->id;
    }

    public static function assertCanManageDoctorVisitNote(IpdDoctorVisitNote $record): void
    {
        if (! self::canManageDoctorVisitNote($record)) {
            throw ValidationException::withMessages([
                'doctor_visit_note' => 'You can only manage your own visit note records.',
            ]);
        }
    }

    public static function resolveGpeDoctorId(Visit $visit, ?int $requestedDoctorId): int
    {
        $careTeamDoctorIds = self::careTeamDoctorIds($visit);

        if ($careTeamDoctorIds === []) {
            throw ValidationException::withMessages([
                'doctor_id' => 'Add at least one doctor to the Care Team before recording GPE.',
            ]);
        }

        $authDoctor = self::authDoctor();

        if ($authDoctor) {
            if (! self::isOnCareTeam($visit, (int) $authDoctor->id)) {
                throw ValidationException::withMessages([
                    'doctor_id' => 'You must be on the care team before recording GPE.',
                ]);
            }

            return (int) $authDoctor->id;
        }

        if (! $requestedDoctorId || ! self::isOnCareTeam($visit, $requestedDoctorId)) {
            throw ValidationException::withMessages([
                'doctor_id' => 'Please select an examining doctor from the care team.',
            ]);
        }

        return $requestedDoctorId;
    }

    public static function resolveOrderDoctorId(Visit $visit, ?int $requestedDoctorId): int
    {
        if ($visit->visit_type !== 'ipd') {
            if (! $visit->doctor_id) {
                throw ValidationException::withMessages([
                    'doctor_id' => 'Please assign a doctor to this visit first.',
                ]);
            }

            return (int) $visit->doctor_id;
        }

        self::ensureIpdVisit($visit);

        $careTeamDoctorIds = self::careTeamDoctorIds($visit);

        if ($careTeamDoctorIds === []) {
            throw ValidationException::withMessages([
                'doctor_id' => 'Add at least one doctor to the Care Team before creating orders.',
            ]);
        }

        $authDoctor = self::authDoctor();

        if ($authDoctor && self::isOnCareTeam($visit, (int) $authDoctor->id)) {
            return (int) $authDoctor->id;
        }

        if ($requestedDoctorId && self::isOnCareTeam($visit, $requestedDoctorId)) {
            return $requestedDoctorId;
        }

        throw ValidationException::withMessages([
            'doctor_id' => 'Please select the ordering doctor from the active care team.',
        ]);
    }

    public static function resolveVisitNoteDoctorId(Visit $visit, ?int $requestedDoctorId): int
    {
        self::ensureIpdVisit($visit);

        $authDoctor = self::authDoctor();

        if ($authDoctor) {
            if (! self::isOnCareTeam($visit, (int) $authDoctor->id)) {
                throw ValidationException::withMessages([
                    'doctor_id' => 'You must be on the care team to record visit notes.',
                ]);
            }

            return (int) $authDoctor->id;
        }

        if (! $requestedDoctorId) {
            throw ValidationException::withMessages([
                'doctor_id' => 'Please select a doctor from the care team.',
            ]);
        }

        if (! self::isOnCareTeam($visit, $requestedDoctorId)) {
            throw ValidationException::withMessages([
                'doctor_id' => 'Visit notes can only be recorded by an active care team member.',
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

    private static function isUniqueConstraintViolation(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = $e->errorInfo[1] ?? null;

        return in_array($sqlState, ['23000', '23505', 'HY000'], true)
            || in_array($driverCode, [1062, 19], true);
    }
}
