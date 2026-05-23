<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CementNotificationTestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[SIG] Test Email Notifikasi Berhasil',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cement-notification-test',
        );
    }
}
