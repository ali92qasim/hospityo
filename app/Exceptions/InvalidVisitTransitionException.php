<?php

namespace App\Exceptions;

use App\Enums\VisitStatus;
use App\Models\Visit;
use RuntimeException;

class InvalidVisitTransitionException extends RuntimeException
{
    public function __construct(Visit $visit, VisitStatus $target)
    {
        parent::__construct(sprintf(
            'Cannot transition %s visit #%d from "%s" to "%s".',
            $visit->visit_type,
            $visit->id,
            $visit->status,
            $target->value,
        ));
    }
}
