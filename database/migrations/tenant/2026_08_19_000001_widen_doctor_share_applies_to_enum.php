<?php

use App\Services\LabImagingSchemaSplit;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new LabImagingSchemaSplit)->run();
    }

    public function down(): void
    {
        // Keep lab/imaging applies_to values; shrinking the enum would truncate rows.
    }
};
