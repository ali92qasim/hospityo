<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table due to its limitations with dropping columns
        Schema::dropIfExists('lab_result_items_backup');
        
        // Create backup table with existing data
        DB::statement('CREATE TABLE lab_result_items_backup AS SELECT * FROM lab_result_items');
        
        // Drop the original table
        Schema::dropIfExists('lab_result_items');
        
        // Recreate the table with correct structure
        Schema::create('lab_result_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_result_id')->constrained()->onDelete('cascade');
            $table->foreignId('lab_test_parameter_id')->constrained()->onDelete('cascade');
            $table->string('value')->nullable();
            $table->string('unit')->nullable();
            $table->enum('flag', ['N', 'H', 'L', 'HH', 'LL', 'A'])->nullable(); // Normal, High, Low, Critical High/Low, Abnormal
            $table->text('comment')->nullable();
            $table->foreignId('entered_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('entered_at');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->unique(['lab_result_id', 'lab_test_parameter_id']);
        });
        
        // Note: Data migration would need to be handled separately if there's existing data
        // that needs to be preserved with proper lab_result_id mapping
        
        // Clean up backup table
        Schema::dropIfExists('lab_result_items_backup');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new table
        Schema::dropIfExists('lab_result_items');
        
        // Recreate the original table structure
        Schema::create('lab_result_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('lab_test_parameter_id')->constrained()->onDelete('cascade');
            $table->string('value')->nullable();
            $table->string('unit')->nullable();
            $table->enum('flag', ['N', 'H', 'L', 'HH', 'LL', 'A'])->nullable();
            $table->text('comment')->nullable();
            $table->foreignId('entered_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('entered_at');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->unique(['lab_order_id', 'lab_test_parameter_id']);
        });
    }
};
