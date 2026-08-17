<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Simple table para lang malaman kung anong webhook events na
        // ang na-process natin — iniiwas nitong ma-duplicate ang processing
        // kasi paulit-ulit magpapadala ang PayMongo hanggang 12x kung
        // hindi agad sumagot ng 200 ang server natin.
        Schema::create('paymongo_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paymongo_webhook_events');
    }
};
