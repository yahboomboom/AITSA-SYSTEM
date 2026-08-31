<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same idea as paymongo_webhook_events: DocuSign Connect will retry a
     * webhook delivery if it doesn't get a fast 200 response, so we keep a
     * record of event IDs we've already handled to avoid double-processing.
     */
    public function up(): void
    {
        Schema::create('docusign_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docusign_webhook_events');
    }
};