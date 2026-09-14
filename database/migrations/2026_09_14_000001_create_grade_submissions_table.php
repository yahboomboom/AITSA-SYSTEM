<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('draft'); // draft | pending_chair | pending_registrar | approved
            $table->string('remarks', 500)->nullable();
            $table->string('rejected_by')->nullable(); // chair | registrar
            $table->foreignId('chair_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('chair_at')->nullable();
            $table->foreignId('registrar_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('registrar_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_submissions');
    }
};
