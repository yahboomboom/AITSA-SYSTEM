<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'contact_number'))   $table->string('contact_number', 30)->nullable()->after('major');
            if (!Schema::hasColumn('users', 'date_of_birth'))    $table->date('date_of_birth')->nullable()->after('contact_number');
            if (!Schema::hasColumn('users', 'sex'))              $table->string('sex', 10)->nullable()->after('date_of_birth');
            if (!Schema::hasColumn('users', 'address'))          $table->string('address', 500)->nullable()->after('sex');
            if (!Schema::hasColumn('users', 'last_school'))      $table->string('last_school', 255)->nullable()->after('address');
            if (!Schema::hasColumn('users', 'year_graduated'))   $table->string('year_graduated', 10)->nullable()->after('last_school');
            if (!Schema::hasColumn('users', 'applicant_type'))   $table->string('applicant_type', 20)->nullable()->after('year_graduated');
            if (!Schema::hasColumn('users', 'program_level'))    $table->string('program_level', 20)->nullable()->after('applicant_type');
            if (!Schema::hasColumn('users', 'applicant_remarks'))$table->text('applicant_remarks')->nullable()->after('program_level');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'contact_number', 'date_of_birth', 'sex', 'address',
                'last_school', 'year_graduated', 'applicant_type',
                'program_level', 'applicant_remarks',
            ]);
        });
    }
};
