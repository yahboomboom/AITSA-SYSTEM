<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schoolYear = Setting::get('school_year', '2026-2027');
        $semester = (int) Setting::get('semester', '1');

        Schema::table('clearances', function (Blueprint $table) use ($schoolYear, $semester) {
            $table->string('school_year')->default($schoolYear)->after('user_id');
            $table->unsignedTinyInteger('semester')->default($semester)->after('school_year');
        });

        DB::table('clearances')->whereNull('school_year')->update([
            'school_year' => $schoolYear,
            'semester' => $semester,
        ]);

        Schema::table('clearances', function (Blueprint $table) {
            $table->unique(['user_id', 'school_year', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::table('clearances', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'school_year', 'semester']);
            $table->dropColumn(['school_year', 'semester']);
        });
    }
};
