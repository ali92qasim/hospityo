<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Shift Definitions
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');                      // Morning, Evening, Night, General
            $table->string('code', 10)->unique();        // MOR, EVE, NGT, GEN
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('break_duration', 4, 2)->default(0); // hours
            $table->decimal('working_hours', 4, 2);      // effective hours
            $table->integer('grace_minutes')->default(15); // late threshold
            $table->string('color', 7)->default('#3B82F6'); // for calendar display
            $table->boolean('is_overnight')->default(false); // crosses midnight
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Duty Roster (weekly/monthly assignments)
        Schema::create('duty_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->boolean('is_off_day')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
            $table->index(['date', 'shift_id']);
        });

        // Shift Swap Requests
        Schema::create('shift_swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('target_id')->constrained('employees')->onDelete('cascade');
            $table->date('swap_date');
            $table->foreignId('requester_shift_id')->constrained('shifts');
            $table->foreignId('target_shift_id')->constrained('shifts');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_swap_requests');
        Schema::dropIfExists('duty_rosters');
        Schema::dropIfExists('shifts');
    }
};
