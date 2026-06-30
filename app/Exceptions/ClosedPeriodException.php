<?php

namespace App\Exceptions;

use App\Models\FiscalYear;

class ClosedPeriodException extends \RuntimeException
{
    public FiscalYear $fiscalYear;

    public function __construct(FiscalYear $fiscalYear, string $entryDate)
    {
        $this->fiscalYear = $fiscalYear;
        parent::__construct(
            "Cannot post journal entry dated {$entryDate} — fiscal period \"{$fiscalYear->name}\" ({$fiscalYear->start_date->format('M d, Y')} – {$fiscalYear->end_date->format('M d, Y')}) is closed."
        );
    }
}
