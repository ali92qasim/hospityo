<?php

namespace App\Workflows;

use App\Contracts\VisitTypeHandler;
use App\Enums\VisitType;
use App\Models\Visit;
use App\Workflows\Handlers\EmergencyVisitHandler;
use App\Workflows\Handlers\IpdVisitHandler;
use App\Workflows\Handlers\OpdVisitHandler;
use InvalidArgumentException;

class VisitHandlerFactory
{
    public static function for(Visit $visit): VisitTypeHandler
    {
        return match ($visit->visit_type) {
            VisitType::Opd->value => app(OpdVisitHandler::class),
            VisitType::Ipd->value => app(IpdVisitHandler::class),
            VisitType::Emergency->value => app(EmergencyVisitHandler::class),
            default => throw new InvalidArgumentException("Unknown visit type: {$visit->visit_type}"),
        };
    }
}
