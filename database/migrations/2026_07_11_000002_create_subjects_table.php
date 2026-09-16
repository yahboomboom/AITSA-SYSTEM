<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('title');
            $table->unsignedTinyInteger('units');
            $table->unsignedTinyInteger('year_level');
            $table->unsignedTinyInteger('semester');
            $table->enum('mode', ['F2F', 'Online'])->default('F2F');
            $table->timestamps();
            $table->unique(['program_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
