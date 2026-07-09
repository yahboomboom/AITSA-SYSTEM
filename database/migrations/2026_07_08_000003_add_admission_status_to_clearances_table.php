<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearances', function (Blueprint $table) {
            if (!Schema::hasColumn('clearances', 'admission_status')) {
                $table->string('admission_status')->default('Pending')->after('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clearances', function (Blueprint $table) {
            $table->dropColumn('admission_status');
        });
    }
};
