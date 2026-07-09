<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('subject_code', 20);
            $table->enum('status', ['Passed', 'Failed', 'In Progress', 'Dropped'])->default('In Progress');
            $table->string('final_grade', 10)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'subject_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_grades');
    }
};
