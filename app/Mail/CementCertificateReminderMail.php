<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CementCertificateReminderMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<int, array<string, mixed>>  $certificates
     */
    public function __construct(
        public readonly array $certificates,
        public readonly string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[SIG] Reminder Sertifikat Sistem ISO - '.count($this->certificates).' tindak lanjut diperlukan',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cement-certificate-reminder',
        );
    }
}
