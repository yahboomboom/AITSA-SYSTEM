<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearances', function (Blueprint $table) {
            $table->boolean('down_payment_waived')->default(false)->after('provisional_granted_at');
            $table->text('down_payment_waived_reason')->nullable()->after('down_payment_waived');
            $table->foreignId('down_payment_waived_by')->nullable()->after('down_payment_waived_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('down_payment_waived_at')->nullable()->after('down_payment_waived_by');
        });
    }

    public function down(): void
    {
        Schema::table('clearances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('down_payment_waived_by');
            $table->dropColumn(['down_payment_waived', 'down_payment_waived_reason', 'down_payment_waived_at']);
        });
    }
};
