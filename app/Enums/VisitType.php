<?php

namespace App\Enums;

enum VisitType: string
{
    case Opd = 'opd';
    case Ipd = 'ipd';
    case Emergency = 'emergency';
}
