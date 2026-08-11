<?php

namespace App\Enums;

enum VisitStatus: string
{
    case Registered = 'registered';
    case Triaged = 'triaged';
    case VitalsRecorded = 'vitals_recorded';
    case Admitted = 'admitted';
    case WithDoctor = 'with_doctor';
    case TestsOrdered = 'tests_ordered';
    case TestsCompleted = 'tests_completed';
    case Discharged = 'discharged';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Registration',
            self::Triaged => 'Triaged',
            self::VitalsRecorded => 'Vital Signs',
            self::Admitted => 'Admitted',
            self::WithDoctor => 'Consultation',
            self::TestsOrdered => 'Tests Ordered',
            self::TestsCompleted => 'Tests Completed',
            self::Discharged => 'Discharged',
            self::Completed => 'Completed',
        };
    }
}
