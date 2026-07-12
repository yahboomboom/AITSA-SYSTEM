<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriculation_change_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matriculation_change_id')->constrained()->cascadeOnDelete();
            $table->string('action'); // add | drop | swap
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('replaced_section_id')->nullable()->constrained('sections')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculation_change_items');
    }
};
