<?php

namespace App\Services\Cement;

use App\Mail\CementCertificateReminderMail;
use App\Models\CertificateEmailNotificationLog;
use App\Models\KontakPerusahaan;
use App\Models\NotificationSetting;
use App\Models\SertifikatSistemSemen;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CementCertificateEmailNotificationService
{
    /**
     * @return array{recipients: int, certificates: int, sent: int, failed: int, skipped: bool}
     */
    public function sendDueReminders(): array
    {
        $settings = $this->settings();

        if (($settings['is_email_enabled'] ?? '0') !== '1') {
            return ['recipients' => 0, 'certificates' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => true];
        }

        $contacts = KontakPerusahaan::query()
            ->with('perusahaanSemen')
            ->where('is_active', true)
            ->whereHas('perusahaanSemen', fn ($query) => $query->where('is_active', true))
            ->get();
        $recipients = $this->recipients($contacts, $settings);

        $certificates = $this->dueCertificates($settings);
        if ($certificates === []) {
            return ['recipients' => count($recipients), 'certificates' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => false];
        }

        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient['email'])->send(new CementCertificateReminderMail(
                    $certificates,
                    $recipient['name'],
                ));

                foreach ($certificates as $certificate) {
                    $this->logSend($recipient['contact'], $recipient['email'], $certificate, 'sent');
                }

                $sent++;
            } catch (Throwable $throwable) {
                report($throwable);
                $failed++;

                foreach ($certificates as $certificate) {
                    $this->logSend($recipient['contact'], $recipient['email'], $certificate, 'failed', $throwable->getMessage());
                }
            }
        }

        app(AuditLogger::class)->log('cement_certificate_email_notifications_sent', null, 'Email reminder sertifikat semen diproses.', null, [
            'recipients' => count($recipients),
            'certificates' => count($certificates),
            'sent' => $sent,
            'failed' => $failed,
        ]);

        return [
            'recipients' => count($recipients),
            'certificates' => count($certificates),
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => false,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function dueCertificates(array $settings): array
    {
        $today = now()->startOfDay();
        $maxDay = max($this->warningDays($settings));
        $limit = $today->copy()->addDays($maxDay);

        return $this->formatSystemCertificates(
            SertifikatSistemSemen::query()
                ->with(['isoStandard', 'lokasiPabrik'])
                ->get()
                ->filter(fn (SertifikatSistemSemen $certificate) => $certificate->followUpTargetDate()?->lte($limit))
        )
            ->sortBy('target_date')
            ->values()
            ->all();
    }

    /**
     * @param  EloquentCollection<int, SertifikatSistemSemen>|Collection<int, SertifikatSistemSemen>  $certificates
     * @return Collection<int, array<string, mixed>>
     */
    private function formatSystemCertificates($certificates)
    {
        return $certificates->map(fn ($certificate) => [
            'model_type' => $certificate::class,
            'model_id' => $certificate->id,
            'type' => 'Sistem ISO',
            'system' => trim(($certificate->isoStandard?->code ?? 'ISO').' - '.($certificate->isoStandard?->name ?? 'Sistem Manajemen')),
            'iso_code' => $certificate->isoStandard?->code ?? 'ISO',
            'lokasi' => $certificate->lokasiPabrik?->nama_lokasi ?? '-',
            'mulai_berlaku' => $certificate->issued_at?->format('d M Y') ?? '-',
            'target_date' => $certificate->followUpTargetDate()?->toDateString(),
            'target_date_label' => $certificate->followUpTargetDate()?->format('d M Y') ?? '-',
            'jenis_tindak_lanjut' => $certificate->auditStageLabel(),
            'action_label' => $certificate->followUpActionLabel(),
            'action_url' => route('cement.system-follow-up.confirm', [
                'certificate' => $certificate,
                'action' => $certificate->followUpAction(),
            ]),
            'berlaku_sd' => $certificate->berlaku_sd->format('d M Y'),
            'expires_at' => $certificate->berlaku_sd->toDateString(),
            'status' => $certificate->statusLabel(),
        ]);
    }

    /**
     * @param  EloquentCollection<int, KontakPerusahaan>  $contacts
     * @return array<int, array{email: string, name: string, contact: KontakPerusahaan|null}>
     */
    private function recipients(EloquentCollection $contacts, array $settings): array
    {
        $recipients = $contacts->map(fn (KontakPerusahaan $contact) => [
            'email' => $contact->email,
            'name' => $contact->nama_pic,
            'contact' => $contact,
        ]);

        $internalEmail = $settings['internal_recipient_email'] ?? null;
        if (filter_var($internalEmail, FILTER_VALIDATE_EMAIL)) {
            $recipients->push([
                'email' => $internalEmail,
                'name' => 'Tim Internal',
                'contact' => null,
            ]);
        }

        return $recipients
            ->unique(fn (array $recipient) => strtolower($recipient['email']))
            ->values()
            ->all();
    }

    private function logSend(?KontakPerusahaan $contact, string $recipientEmail, array $certificate, string $status, ?string $error = null): void
    {
        CertificateEmailNotificationLog::query()->create([
            'certificate_type' => $certificate['model_type'],
            'certificate_id' => $certificate['model_id'],
            'kontak_perusahaan_id' => $contact?->id,
            'recipient_email' => $recipientEmail,
            'notification_type' => 'cement_system_follow_up',
            'certificate_expires_at' => Carbon::parse($certificate['expires_at']),
            'status' => $status,
            'error_message' => $error,
            'sent_at' => now(),
        ]);
    }

    /**
     * @return list<int>
     */
    private function warningDays(array $settings): array
    {
        $value = $settings['expiry_warning_days'] ?? '90,60,30,7';

        return collect(explode(',', $value))
            ->map(fn ($day) => (int) trim($day))
            ->filter(fn (int $day) => $day > 0)
            ->values()
            ->all() ?: [90, 60, 30, 7];
    }

    /**
     * @return array<string, string>
     */
    private function settings(): array
    {
        $defaults = [
            'internal_recipient_email' => 'abdullahsyauqillah02@gmail.com',
            'expiry_warning_days' => '90,60,30,7',
            'is_email_enabled' => '1',
        ];

        try {
            return NotificationSetting::query()
                ->whereIn('key', array_keys($defaults))
                ->pluck('value', 'key')
                ->union($defaults)
                ->all();
        } catch (QueryException) {
            return $defaults;
        }
    }
}
