<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_ledgers', function (Blueprint $table) {
            $table->string('gateway', 20)->nullable()->after('status');
            $table->string('checkout_session_id')->nullable()->index()->after('gateway');
            $table->timestamp('paid_at')->nullable()->after('checkout_session_id');
            $table->unsignedBigInteger('processed_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('transaction_ledgers', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'checkout_session_id', 'paid_at']);
        });
    }
};
