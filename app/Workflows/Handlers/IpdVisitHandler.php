<?php

namespace App\Workflows\Handlers;

use App\Contracts\VisitTypeHandler;
use App\Enums\VisitType;
use App\Models\Bed;
use App\Models\Doctor;
use App\Models\Visit;
use App\Services\IpdDraftBillService;
use Illuminate\Support\Collection;

class IpdVisitHandler implements VisitTypeHandler
{
    public function type(): VisitType
    {
        return VisitType::Ipd;
    }

    public function workflowData(Visit $visit): array
    {
        $visit->loadMissing([
            'ipdDetails',
            'admission.bed.ward',
            'admission.advances',
            'careTeam.doctor',
            'primaryDoctor.doctor',
            'allVitalSigns',
            'ipdGpeRecords',
            'doctorVisitNotes',
            'draftBill',
        ]);

        $draftBill = IpdDraftBillService::resolveForVisit($visit);
        $settlement = null;

        if ($visit->admission?->status === 'active') {
            $settlement = [
                'total_advances' => $visit->admission->total_advances,
                'draft_charges' => (float) ($draftBill?->total_amount ?? 0),
                'credit_balance' => $visit->admission->credit_balance,
                'amount_due' => max(0, ($draftBill?->due_amount ?? 0)),
            ];
        }

        return [
            'steps' => [
                'registered' => 'Registration',
                'vitals_recorded' => 'Vital Signs',
                'admitted' => 'Admitted',
                'with_doctor' => 'Treatment',
                'discharged' => 'Discharged',
            ],
            'default_tab' => 'admission',
            'show_investigations' => true,
            'consultation_label' => 'Consultation',
            'available_beds' => Bed::with('ward')->where('status', 'available')->get(),
            'draft_bill' => $draftBill,
            'settlement' => $settlement,
            'expected_discharge_date' => $visit->ipdDetails?->expected_discharge_date,
        ];
    }

    public function resolveDoctors(Visit $visit): Collection
    {
        return Doctor::where('status', 'active')->get();
    }

    public function canComplete(Visit $visit): bool
    {
        return false;
    }
}
