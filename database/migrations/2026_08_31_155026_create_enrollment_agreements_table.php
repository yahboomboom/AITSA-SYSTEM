<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per DocuSign "envelope" (the digital document) we send out for
     * signing. A user can only have one active/most-recent agreement — we
     * don't need history beyond that for this proof-of-concept.
     */
    public function up(): void
    {
        Schema::create('enrollment_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('envelope_id')->nullable()->index();

            // 'sent'      -> envelope created, waiting for the student to sign.
            // 'completed' -> student has signed (confirmed either by the DocuSign
            //                webhook or by us polling the Envelopes API).
            // 'declined'  -> student explicitly declined to sign.
            // 'voided'    -> envelope expired/was cancelled.
            $table->string('status')->default('sent');

            // Opaque random token embedded in the return URL we hand to DocuSign,
            // so we can recognize the student when DocuSign redirects them back —
            // without relying on Laravel's signed-URL middleware, since DocuSign
            // appends its own "?event=..." query parameter to whatever return URL
            // we give it, which would otherwise break signature validation.
            $table->string('return_token', 64)->unique();

            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_agreements');
    }
};