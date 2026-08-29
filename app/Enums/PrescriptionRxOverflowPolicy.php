<?php

namespace App\Enums;

enum PrescriptionRxOverflowPolicy: string
{
    case SecondPagePlain = 'second_page_plain';
    case ShrinkFont = 'shrink_font';
    case CapWithNote = 'cap_with_note';
}
