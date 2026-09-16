<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearances', function (Blueprint $table) {
            $table->string('remarks', 500)->nullable()->after('registrar_status');
            $table->dropColumn(['library_status', 'clinic_status']);
        });
    }

    public function down(): void
    {
        Schema::table('clearances', function (Blueprint $table) {
            $table->dropColumn('remarks');
            $table->string('library_status')->default('Approved');
            $table->string('clinic_status')->default('Approved');
        });
    }
};
