<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipd_duty_doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users');
            $table->timestamps();

            $table->unique(['visit_id', 'doctor_id']);
            $table->index('visit_id');
        });

        if (Schema::hasColumn('visits', 'duty_doctor_id')) {
            $rows = DB::table('visits')
                ->whereNotNull('duty_doctor_id')
                ->get(['id', 'duty_doctor_id']);

            foreach ($rows as $row) {
                DB::table('ipd_duty_doctors')->insert([
                    'visit_id'    => $row->id,
                    'doctor_id'   => $row->duty_doctor_id,
                    'assigned_by' => DB::table('users')->value('id') ?? 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            Schema::table('visits', function (Blueprint $table) {
                $table->dropConstrainedForeignId('duty_doctor_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            if (! Schema::hasColumn('visits', 'duty_doctor_id')) {
                $table->foreignId('duty_doctor_id')
                    ->nullable()
                    ->after('doctor_id')
                    ->constrained('doctors')
                    ->nullOnDelete();
            }
        });

        $firstAssignments = DB::table('ipd_duty_doctors')
            ->select('visit_id', DB::raw('MIN(id) as id'))
            ->groupBy('visit_id')
            ->get();

        foreach ($firstAssignments as $assignment) {
            $doctorId = DB::table('ipd_duty_doctors')->where('id', $assignment->id)->value('doctor_id');

            DB::table('visits')
                ->where('id', $assignment->visit_id)
                ->update(['duty_doctor_id' => $doctorId]);
        }

        Schema::dropIfExists('ipd_duty_doctors');
    }
};
