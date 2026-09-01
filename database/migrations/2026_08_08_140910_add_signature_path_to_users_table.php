<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {   // function to add signature path to users table by renz
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
    {   // function to drop signature path from users table by renz
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