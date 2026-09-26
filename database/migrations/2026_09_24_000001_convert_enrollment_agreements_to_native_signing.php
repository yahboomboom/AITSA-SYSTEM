<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enrollment agreements are now signed natively in-app (canvas signature
     * + audit trail) instead of via DocuSign, so the envelope-lifecycle
     * columns are gone — a row only ever exists once actually signed.
     */
    public function up(): void
    {
        Schema::table('enrollment_agreements', function (Blueprint $table) {
            $table->dropIndex(['envelope_id']);
            $table->dropUnique(['return_token']);
            $table->dropColumn(['envelope_id', 'return_token', 'status']);
            $table->string('signature_path')->after('user_id');
            $table->string('ip_address', 45)->nullable()->after('signature_path');
            $table->string('user_agent')->nullable()->after('ip_address');
            $table->string('agreement_hash', 64)->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_agreements', function (Blueprint $table) {
            $table->dropColumn(['signature_path', 'ip_address', 'user_agent', 'agreement_hash']);
            $table->string('envelope_id')->nullable()->index();
            $table->string('status')->default('sent');
            $table->string('return_token', 64)->unique();
        });
    }
};
