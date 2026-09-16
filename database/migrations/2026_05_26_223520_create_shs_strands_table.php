<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shs_strands', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // ABM, HUMSS, HE-TOURISM [cite: 6]
            $table->string('name');           // Accountancy, Business, and Management, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shs_strands');
    }
};