<?php

namespace App\Console\Commands;

use App\Services\CertificateExpiryNotificationService;
use Illuminate\Console\Command;

class GenerateCertificateExpiryNotifications extends Command
{
    protected $signature = 'notifications:generate-certificate-expiry';

    protected $description = 'Generate internal notifications for certificate expiry and ISO system follow-up actions.';

    public function handle(CertificateExpiryNotificationService $service): int
    {
        $result = $service->generate();

        $this->info('Notifikasi kedaluwarsa sertifikat selesai diproses.');
        $this->line('Penerima: '.$result['recipients']);
        $this->line('Sertifikat memenuhi syarat: '.$result['eligible_certificates']);
        $this->line('Notifikasi dibuat: '.$result['created']);
        $this->line('Notifikasi diperbarui: '.$result['updated']);
        $this->line('Notifikasi ditutup: '.$result['dismissed']);

        return self::SUCCESS;
    }
}
