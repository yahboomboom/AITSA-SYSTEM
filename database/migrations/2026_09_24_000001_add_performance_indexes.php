<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * These columns are filtered on almost every authenticated page load
 * (see resources/views/partials/notif-script.blade.php, plus the
 * registrar/cashier/admin dashboards and reports) but had no indexes,
 * so every one of those counts/queries was a full table scan. As the
 * clearances/users/transaction_ledgers tables grow, this is what causes
 * "Maximum execution time of 30 seconds exceeded" under load.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
        });

        Schema::table('clearances', function (Blueprint $table) {
            // Matches the WHERE school_year + semester + <status> pattern used
            // everywhere (notif-script, registrar/cashier/chair dashboards, reports).
            $table->index(['school_year', 'semester', 'chair_status'], 'clearances_syr_sem_chair_idx');
            $table->index(['school_year', 'semester', 'cashier_status'], 'clearances_syr_sem_cashier_idx');
            $table->index(['school_year', 'semester', 'registrar_status'], 'clearances_syr_sem_registrar_idx');
        });

        Schema::table('transaction_ledgers', function (Blueprint $table) {
            $table->index('status');
            $table->index(['gateway', 'status']);
            $table->index('paid_at');
            $table->index('fee_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
        });

        Schema::table('clearances', function (Blueprint $table) {
            $table->dropIndex('clearances_syr_sem_chair_idx');
            $table->dropIndex('clearances_syr_sem_cashier_idx');
            $table->dropIndex('clearances_syr_sem_registrar_idx');
        });

        Schema::table('transaction_ledgers', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['gateway', 'status']);
            $table->dropIndex(['paid_at']);
            $table->dropIndex(['fee_type']);
        });
    }
};