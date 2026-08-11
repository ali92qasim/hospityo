<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn([
                'discharge_datetime',
                'chief_complaint',
                'diagnosis',
                'treatment',
                'notes',
                'total_charges',
                'bed_no',
                'room_no',
                'priority',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->datetime('discharge_datetime')->nullable()->after('visit_datetime');
            $table->text('chief_complaint')->nullable()->after('discharge_datetime');
            $table->text('diagnosis')->nullable()->after('chief_complaint');
            $table->text('treatment')->nullable()->after('diagnosis');
            $table->text('notes')->nullable()->after('treatment');
            $table->decimal('total_charges', 10, 2)->default(0)->after('notes');
            $table->string('bed_no')->nullable()->after('total_charges');
            $table->string('room_no')->nullable()->after('bed_no');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium')->after('room_no');
        });
    }
};
