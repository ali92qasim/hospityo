<?php

namespace App\Services;

use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\LabResultItem;
use App\Models\LabTest;
use App\Models\LabTestParameter;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Carbon;

/**
 * Synthetic lab report payload for settings accent preview when no printable order exists.
 * Builds unsaved Eloquent/view-model hybrids so the print Blade stays unchanged
 * and the tenant database is not polluted with fixture rows.
 */
final class LabReportPreviewFixture
{
    public const PATIENT_NAME = 'Preview Sample Patient';

    /**
     * @return array{
     *     order: LabOrder,
     *     pages: array<int, array{sections: array<int, array<string, mixed>>, row_cost: int}>,
     *     primaryResult: LabResult,
     *     comments: array<int, string>,
     *     patient_band: array<string, mixed>,
     *     reviewers: list<array{name: string, qualification: ?string, specialization: ?string}>
     * }
     */
    public static function report(): array
    {
        $collectedAt = Carbon::now()->subHours(4);
        $reportedAt = Carbon::now()->subHour();
        $visitAt = Carbon::now()->subDay()->setTime(10, 30);

        $patient = new Patient([
            'name' => self::PATIENT_NAME,
            'gender' => 'female',
            'age' => 32,
            'phone' => '03000000000',
            'patient_no' => 'PREV-001',
        ]);

        $doctor = new \App\Models\Doctor([
            'name' => 'Preview Referring Doctor',
            'qualification' => 'MBBS',
            'specialization' => 'General',
        ]);

        $visit = new Visit([
            'visit_type' => 'opd',
            'visit_datetime' => $visitAt,
        ]);

        $order = new LabOrder([
            'order_number' => 'LAB-PREVIEW-001',
            'share_token' => 'preview-fixture-token',
            'priority' => 'routine',
            'status' => 'reported',
            'ordered_at' => $collectedAt->copy()->subHours(2),
            'sample_collected_at' => $collectedAt,
            'completed_at' => $reportedAt,
            'clinical_notes' => 'Accent preview fixture note.',
        ]);
        $order->setRelation('patient', $patient);
        $order->setRelation('doctor', $doctor);
        $order->setRelation('visit', $visit);

        $investigation = new LabTest([
            'code' => 'PREV',
            'name' => 'Complete Blood Count',
            'category' => 'hematology',
        ]);

        $parameter = new LabTestParameter([
            'parameter_name' => 'Hemoglobin',
            'unit' => 'g/dL',
            'data_type' => 'numeric',
            'reference_ranges' => ['normal' => '12-16'],
        ]);

        $item = new LabResultItem([
            'value' => '13.4',
            'unit' => 'g/dL',
            'flag' => 'N',
        ]);
        $item->setRelation('parameter', $parameter);
        $item->previous_values = [];

        $pathologist = new User([
            'name' => 'Preview Pathologist',
        ]);

        $primaryResult = new LabResult([
            'status' => 'final',
            'comments' => 'Preview fixture comment.',
            'reported_at' => $reportedAt,
            'tested_at' => $reportedAt->copy()->subHour(),
            'verified_at' => $reportedAt,
        ]);
        $primaryResult->setRelation('pathologist', $pathologist);

        $hospitalName = trim((string) setting('hospital_name', config('app.name', 'Hospital Management System')));
        $hospitalAddress = trim((string) setting('hospital_address', ''));
        $registrationLocation = $hospitalName;
        if ($hospitalAddress !== '') {
            $registrationLocation .= ', '.$hospitalAddress;
        }

        return [
            'order' => $order,
            'pages' => [
                [
                    'sections' => [
                        [
                            'investigation' => $investigation,
                            'items' => [$item],
                            'row_cost' => 4,
                        ],
                    ],
                    'row_cost' => 4,
                ],
            ],
            'primaryResult' => $primaryResult,
            'comments' => ['Preview fixture comment.'],
            'patient_band' => [
                'registration_location' => $registrationLocation,
                'registration_date' => $visitAt,
                'case_number' => $order->order_number,
                'note' => $order->clinical_notes,
                'department' => 'Hematology',
                'consultant' => 'Preview Reviewer',
            ],
            'reviewers' => [
                [
                    'name' => 'Preview Reviewer',
                    'qualification' => 'FCPS',
                    'specialization' => 'Pathology',
                ],
            ],
        ];
    }
}
