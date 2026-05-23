<?php

namespace App\Console\Commands;

use App\Services\Cement\CementCertificateEmailNotificationService;
use Illuminate\Console\Command;

class SendCementCertificateEmailNotifications extends Command
{
    protected $signature = 'cement:send-certificate-email-notifications';

    protected $description = 'Send email reminders for cement certificates that are expired or nearing expiration.';

    public function handle(CementCertificateEmailNotificationService $service): int
    {
        $result = $service->sendDueReminders();

        if ($result['skipped']) {
            $this->info('Email notifikasi sertifikat semen sedang nonaktif.');

            return self::SUCCESS;
        }

        $this->info('Email notifikasi sertifikat semen selesai diproses.');
        $this->line('Penerima: '.$result['recipients']);
        $this->line('Sertifikat: '.$result['certificates']);
        $this->line('Terkirim: '.$result['sent']);
        $this->line('Gagal: '.$result['failed']);

        return self::SUCCESS;
    }
}
