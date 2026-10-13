<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Codul care confirmă ștergerea contului.
 *
 * Mailable, nu `Mail::send()` cu o vedere: un mesaj trimis brut nu e numărat de
 * `Mail::assertSent`, deci testul care verifică „s-a trimis exact un email" ar
 * trece degeaba.
 *
 * Doar text. Un cod de șase cifre n-are nevoie de HTML, iar varianta text ajunge
 * întreagă pe orice client, inclusiv pe telefoane vechi.
 */
class DeletionCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $cod,
        public int $minute,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Codul de ștergere a contului eBac');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.account-deletion-code');
    }
}
