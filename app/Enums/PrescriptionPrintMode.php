<?php

namespace App\Enums;

enum PrescriptionPrintMode: string
{
    case OverlayPhysical = 'overlay_physical';
    case DigitizedBackground = 'digitized_background';
}
