<?php

namespace App\Contracts;

use App\Enums\VisitType;
use App\Models\Visit;
use Illuminate\Support\Collection;

interface VisitTypeHandler
{
    public function type(): VisitType;

    /** @return array<string, mixed> */
    public function workflowData(Visit $visit): array;

    public function resolveDoctors(Visit $visit): Collection;

    public function canComplete(Visit $visit): bool;

    public function canConsult(Visit $visit): bool;

    public function canPrescribe(Visit $visit): bool;

    public function canOrderLabs(Visit $visit): bool;

    public function resolveInitialTab(Visit $visit): string;

    public function showOrderDoctorPicker(Visit $visit, ?\App\Models\Doctor $authDoctor): bool;
}
