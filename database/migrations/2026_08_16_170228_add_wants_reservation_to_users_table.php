<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Captured at application time — whether the applicant SAID they
            // want to reserve a slot. This is just intent, not proof of
            // payment. `is_reserved` (a separate column) only flips to true
            // once the ₱500 fee is actually confirmed paid, either via the
            // Admission "Mark as Paid" toggle or the PayMongo webhook.
            $table->boolean('wants_reservation')->default(false)->after('is_reserved');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('wants_reservation');
        });
    }
};
