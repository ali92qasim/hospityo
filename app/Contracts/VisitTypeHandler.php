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
}
