<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearances', function (Blueprint $table) {
            $table->boolean('is_provisional')->default(false)->after('registrar_signed_at');
            $table->text('provisional_reason')->nullable()->after('is_provisional');
            $table->foreignId('provisional_granted_by')->nullable()->after('provisional_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('provisional_granted_at')->nullable()->after('provisional_granted_by');
        });
    }

    public function down(): void
    {
        Schema::table('clearances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('provisional_granted_by');
            $table->dropColumn(['is_provisional', 'provisional_reason', 'provisional_granted_at']);
        });
    }
};
