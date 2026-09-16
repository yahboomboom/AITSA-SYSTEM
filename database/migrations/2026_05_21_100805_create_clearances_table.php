<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearances', function (Blueprint $table) {
            $table->id();
            // Link directly to the id column on your users table
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Status tracks for each clearance station
            $table->string('chair_status')->default('Pending');     // Department Chair
            $table->string('cashier_status')->default('Pending');   // Cashier / Finance
            $table->string('registrar_status')->default('Pending'); // Registrar Office
            $table->string('library_status')->default('Approved');   // Automatically clean by default
            $table->string('clinic_status')->default('Approved');    // Automatically clean by default
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearances');
    }
};