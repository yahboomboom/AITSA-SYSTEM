<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The `students` table was an early normalized-profile design that was
     * never wired up — every real student field (program_key, year_level,
     * major, etc.) lives directly on `users`. Nothing ever writes a row
     * here, so it's safe to drop.
     */
    public function up(): void
    {
        Schema::dropIfExists('students');
    }

    public function down(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('academic_level', ['SHS', 'COLLEGE'])->default('COLLEGE');
            $table->foreignId('strand_id')->nullable()->constrained('shs_strands')->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->string('year_level')->nullable();
            $table->enum('learning_modality', ['Face-to-Face', 'Digital', 'Printed'])->nullable();
            $table->timestamps();
        });
    }
};
