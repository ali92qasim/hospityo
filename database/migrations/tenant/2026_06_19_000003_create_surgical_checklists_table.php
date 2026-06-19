<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surgical_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('surgeries')->cascadeOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('incomplete'); // incomplete, sign_in_done, time_out_done, completed
            $table->timestamp('sign_in_completed_at')->nullable();
            $table->timestamp('time_out_completed_at')->nullable();
            $table->timestamp('sign_out_completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('surgical_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgical_checklist_id')->constrained('surgical_checklists')->cascadeOnDelete();
            $table->string('phase'); // sign_in, time_out, sign_out
            $table->string('item_key'); // e.g. patient_identity_confirmed
            $table->string('label'); // human-readable label
            $table->boolean('is_checked')->default(false);
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surgical_checklist_items');
        Schema::dropIfExists('surgical_checklists');
    }
};
