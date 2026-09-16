<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The 2026_08_09_013256 migration that was supposed to add this column
     * shipped with an empty up()/down() body and never actually added it,
     * so document uploads throw a "no column named signed_at" SQL error in
     * every environment. That migration already ran (and can't be safely
     * edited after the fact), so this one adds the column for real.
     */
    public function up(): void
    {
        Schema::table('document_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('document_submissions', 'signed_at')) {
                $table->timestamp('signed_at')->nullable()->after('reviewed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('document_submissions', 'signed_at')) {
                $table->dropColumn('signed_at');
            }
        });
    }
};
