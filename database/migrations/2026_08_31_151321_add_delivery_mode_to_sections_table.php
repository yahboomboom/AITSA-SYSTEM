<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a per-section "delivery_mode" so a registrar/admin can mark a
     * specific class block as Face-to-Face or Online — independent from
     * the subject-level "mode" field, since one subject can offer both a
     * face-to-face block and an online block in the same term.
     */
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->enum('delivery_mode', ['Face-to-Face', 'Online'])
                ->default('Face-to-Face')
                ->after('room_id');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('delivery_mode');
        });
    }
};