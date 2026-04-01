<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the table has the wrong foreign key
        if (Schema::hasColumn('lab_result_items', 'lab_order_id')) {
            // Drop the old table and recreate with correct structure
            Schema::dropIfExists('lab_result_items');
            
            Schema::create('lab_result_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lab_result_id')->constrained()->onDelete('cascade');
                $table->foreignId('lab_test_parameter_id')->constrained()->onDelete('cascade');
                $table->string('value')->nullable();
                $table->string('unit')->nullable();
                $table->enum('flag', ['N', 'H', 'L', 'HH', 'LL', 'A'])->default('N')->nullable();
                $table->text('comment')->nullable();
                $table->foreignId('entered_by')->constrained('users')->onDelete('cascade');
                $table->timestamp('entered_at')->useCurrent();
                $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
                
                $table->unique(['lab_result_id', 'lab_test_parameter_id']);
            });
        }
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
