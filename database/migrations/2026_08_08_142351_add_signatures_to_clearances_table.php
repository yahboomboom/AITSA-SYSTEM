<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// This migration adds signature fields to the clearances table, allowing for tracking of who signed and when for chair, cashier, and registrar roles.
return new class extends Migration
{
    public function up(): void
    {   // Add signature fields to the clearances table
        Schema::table('clearances', function (Blueprint $table) {
            $table->foreignId('chair_signed_by')->nullable()->after('chair_status')->constrained('users')->nullOnDelete();
            $table->timestamp('chair_signed_at')->nullable()->after('chair_signed_by');

            $table->foreignId('cashier_signed_by')->nullable()->after('cashier_status')->constrained('users')->nullOnDelete();
            $table->timestamp('cashier_signed_at')->nullable()->after('cashier_signed_by');

            $table->foreignId('registrar_signed_by')->nullable()->after('registrar_status')->constrained('users')->nullOnDelete();
            $table->timestamp('registrar_signed_at')->nullable()->after('registrar_signed_by');
        });
    }

    public function down(): void
    {   // Remove signature fields from the clearances table
        Schema::table('clearances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chair_signed_by');
            $table->dropColumn('chair_signed_at');
            $table->dropConstrainedForeignId('cashier_signed_by');
            $table->dropColumn('cashier_signed_at');
            $table->dropConstrainedForeignId('registrar_signed_by');
            $table->dropColumn('registrar_signed_at');
        });
    }
};