<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            // Announcements are title-only now; old details text is kept but no longer required.
            $table->text('body')->nullable()->change();
            $table->string('link_url', 2048)->nullable()->after('attachment_name');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('link_url');
        });
    }
};
