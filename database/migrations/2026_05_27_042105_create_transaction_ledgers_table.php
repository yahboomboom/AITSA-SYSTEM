<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_ledgers', function (Blueprint $table) {
            $table->id();
            // Match your clearance table structure exactly
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('reference_no')->unique(); // Holds 'TXN-XXXXX-WIT'
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('Settled');
            $table->unsignedBigInteger('processed_by'); // Staff account tracking
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_ledgers');
    }
};