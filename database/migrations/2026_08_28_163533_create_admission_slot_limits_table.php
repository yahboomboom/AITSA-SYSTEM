<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Store which curriculum an applicant picked (matches the "id" keys in
        // config/curricula.php, e.g. "bsoa", "bk3"), so we can count slot usage.
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'program_key')) {
                $table->string('program_key', 30)->nullable()->after('major');
            }
        });

        // One row per curriculum per school year: total slot limit + how many
        // sections it is split into.
        Schema::create('admission_slot_limits', function (Blueprint $table) {
            $table->id();
            $table->string('program_key', 30);
            $table->string('program_name');
            $table->string('school_year', 20);
            $table->unsignedInteger('total_slots')->default(200);
            $table->unsignedInteger('sections')->default(4);
            $table->timestamps();

            // A curriculum can only have one active slot-limit row per school year.
            $table->unique(['program_key', 'school_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_slot_limits');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'program_key')) {
                $table->dropColumn('program_key');
            }
        });
    }
};