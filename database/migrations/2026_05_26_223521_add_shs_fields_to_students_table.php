<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 💻 Laravel's native, cross-database way to toggle foreign keys
        Schema::disableForeignKeyConstraints();

        Schema::table('students', function (Blueprint $table) {
            // Attach the SHS strand foreign key only when the referenced tables exist.
            if (Schema::hasTable('shs_strands')) {
                $table->foreign('strand_id')->references('id')->on('shs_strands')->nullOnDelete();
            }

            if (Schema::hasTable('programs')) {
                $table->foreign('program_id')->references('id')->on('programs')->nullOnDelete();
            }
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                // Pass an array to safely drop constraints
                $table->dropForeign(['strand_id']);
                $table->dropForeign(['program_id']);
            });
        }

        Schema::enableForeignKeyConstraints();
    }
};