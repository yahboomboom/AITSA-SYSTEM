<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Admission-stage withdrawal (no-show or informed withdrawal) —
            // kept on the record instead of deleting the user.
            $table->timestamp('withdrawn_at')->nullable();
            $table->string('withdrawal_reason', 20)->nullable(); // 'no_show' | 'withdrew'
            $table->text('withdrawal_note')->nullable();
            $table->foreignId('withdrawn_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('withdrawn_by');
            $table->dropColumn(['withdrawn_at', 'withdrawal_reason', 'withdrawal_note']);
        });
    }
};
