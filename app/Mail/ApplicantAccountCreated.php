<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicantAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $student,
        public string $loginId,
        public string $password,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your AITSA Student Account Is Ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.applicant-account-created',
        );
    }
}
