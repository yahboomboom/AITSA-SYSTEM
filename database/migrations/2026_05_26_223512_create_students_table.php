<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table) {
                $table->id();
                
                // Unified Academic Tracking
                $table->enum('academic_level', ['SHS', 'COLLEGE'])->default('COLLEGE');
                
                // Foreign Keys (Unsigned Big Integers matching the table IDs)
                $table->unsignedBigInteger('strand_id')->nullable();
                $table->unsignedBigInteger('program_id')->nullable();
                
                // Shared fields derived from enrollment profile requirements
                $table->string('year_level')->nullable(); // e.g., "Grade 11", "1st Year"
                $table->enum('learning_modality', ['Face-to-Face', 'Digital', 'Printed'])->nullable(); // [cite: 8, 9, 10]
                
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};