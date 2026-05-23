<?php

namespace App\Services;

use App\Enums\NotificationStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\SertifikatSistemSemen;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class CertificateExpiryNotificationService
{
    private const NOTIFICATION_TYPE = 'certificate_expiry_reminder';

    private const SYSTEM_NOTIFICATION_TYPE = 'cement_system_follow_up';

    /**
     * @return array{created: int, updated: int, dismissed: int, eligible_certificates: int, recipients: int}
     */
    public function generate(): array
    {
        $recipientIds = User::query()
            ->whereIn('role', [UserRole::Admin->value, UserRole::Petugas->value])
            ->pluck('id');

        if ($recipientIds->isEmpty()) {
            return [
                'created' => 0,
                'updated' => 0,
                'dismissed' => 0,
                'eligible_certificates' => 0,
                'recipients' => 0,
            ];
        }

        $eligibleCertificates = Certificate::query()
            ->with(['product:id,name'])
            ->monitoringExpiringSoon()
            ->get(['id', 'product_id', 'certificate_number', 'expires_at']);

        $existingNotifications = Notification::query()
            ->where('notification_type', self::NOTIFICATION_TYPE)
            ->whereIn('user_id', $recipientIds)
            ->when(
                $eligibleCertificates->isNotEmpty(),
                fn ($query) => $query->whereIn('certificate_id', $eligibleCertificates->modelKeys()),
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->get()
            ->keyBy(fn (Notification $notification) => $this->notificationKey(
                (int) $notification->user_id,
                (int) $notification->certificate_id,
            ));

        $created = 0;
        $updated = 0;

        foreach ($eligibleCertificates as $certificate) {
            $payload = $this->buildNotificationPayload($certificate);

            foreach ($recipientIds as $recipientId) {
                $key = $this->notificationKey((int) $recipientId, $certificate->id);
                $notification = $existingNotifications->get($key);

                if (! $notification) {
                    Notification::query()->create([
                        'user_id' => $recipientId,
                        'certificate_id' => $certificate->id,
                        ...$payload,
                    ]);

                    $created++;

                    continue;
                }

                if ($notification->status === NotificationStatus::Unread) {
                    $notification->update($payload);
                    $updated++;

                    continue;
                }

                if ($notification->status === NotificationStatus::Dismissed) {
                    $notification->update([
                        ...$payload,
                        'status' => NotificationStatus::Unread,
                        'read_at' => null,
                    ]);
                    $updated++;
                }
            }
        }

        $systemResult = $this->generateSystemCertificateNotifications($recipientIds);
        $dismissed = $this->dismissResolvedNotifications($recipientIds->all(), $eligibleCertificates)
            + $systemResult['dismissed'];

        return [
            'created' => $created + $systemResult['created'],
            'updated' => $updated + $systemResult['updated'],
            'dismissed' => $dismissed,
            'eligible_certificates' => $eligibleCertificates->count() + $systemResult['eligible_certificates'],
            'recipients' => $recipientIds->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNotificationPayload(Certificate $certificate): array
    {
        $daysRemaining = max($certificate->daysUntilExpiry(), 0);

        return [
            'title' => 'Sertifikat akan habis masa berlaku',
            'message' => sprintf(
                'Sertifikat %s untuk produk %s akan habis dalam %d hari pada %s.',
                $certificate->certificate_number,
                $certificate->product?->name ?? 'produk terkait',
                $daysRemaining,
                $certificate->expires_at->format('d M Y'),
            ),
            'notification_type' => self::NOTIFICATION_TYPE,
            'status' => NotificationStatus::Unread,
            'scheduled_at' => now(),
            'sent_at' => now(),
            'data' => [
                'days_remaining' => $daysRemaining,
                'expiry_date' => $certificate->expires_at->toDateString(),
                'certificate_number' => $certificate->certificate_number,
                'product_name' => $certificate->product?->name,
            ],
        ];
    }

    /**
     * @param  list<int>  $recipientIds
     */
    private function dismissResolvedNotifications(array $recipientIds, Collection $eligibleCertificates): int
    {
        return Notification::query()
            ->where('notification_type', self::NOTIFICATION_TYPE)
            ->whereIn('user_id', $recipientIds)
            ->when(
                $eligibleCertificates->isNotEmpty(),
                fn ($query) => $query->whereNotIn('certificate_id', $eligibleCertificates->modelKeys()),
            )
            ->where('status', '!=', NotificationStatus::Dismissed->value)
            ->update([
                'status' => NotificationStatus::Dismissed->value,
            ]);
    }

    private function notificationKey(int $userId, int $certificateId): string
    {
        return $userId.'-'.$certificateId;
    }

    /**
     * @param  SupportCollection<int, int>  $recipientIds
     * @return array{created: int, updated: int, dismissed: int, eligible_certificates: int}
     */
    private function generateSystemCertificateNotifications(SupportCollection $recipientIds): array
    {
        $today = now()->startOfDay();
        $limit = $today->copy()->addDays($this->systemWarningDays());

        $certificates = SertifikatSistemSemen::query()
            ->with(['isoStandard', 'lokasiPabrik', 'auditEvents'])
            ->get()
            ->filter(fn (SertifikatSistemSemen $certificate) => $this->systemFollowUpTargetDate($certificate)?->lte($limit))
            ->values();

        $existingNotifications = Notification::query()
            ->where('notification_type', self::SYSTEM_NOTIFICATION_TYPE)
            ->whereIn('user_id', $recipientIds)
            ->get()
            ->keyBy(fn (Notification $notification) => $this->systemNotificationKey(
                (int) $notification->user_id,
                (int) data_get($notification->data, 'certificate_id'),
                (string) data_get($notification->data, 'follow_up_action'),
            ));

        $created = 0;
        $updated = 0;
        $activeKeys = [];

        foreach ($certificates as $certificate) {
            $payload = $this->buildSystemNotificationPayload($certificate);

            foreach ($recipientIds as $recipientId) {
                $key = $this->systemNotificationKey((int) $recipientId, $certificate->id, $certificate->followUpAction());
                $activeKeys[] = $key;
                $notification = $existingNotifications->get($key);

                if (! $notification) {
                    Notification::query()->create([
                        'user_id' => $recipientId,
                        'certificate_id' => null,
                        ...$payload,
                    ]);

                    $created++;

                    continue;
                }

                if ($notification->status === NotificationStatus::Unread) {
                    $notification->update($payload);
                    $updated++;

                    continue;
                }

                if ($notification->status === NotificationStatus::Dismissed) {
                    $notification->update([
                        ...$payload,
                        'status' => NotificationStatus::Unread,
                        'read_at' => null,
                    ]);
                    $updated++;
                }
            }
        }

        $dismissed = Notification::query()
            ->where('notification_type', self::SYSTEM_NOTIFICATION_TYPE)
            ->whereIn('user_id', $recipientIds)
            ->where('status', '!=', NotificationStatus::Dismissed->value)
            ->get()
            ->reject(fn (Notification $notification) => in_array(
                $this->systemNotificationKey(
                    (int) $notification->user_id,
                    (int) data_get($notification->data, 'certificate_id'),
                    (string) data_get($notification->data, 'follow_up_action'),
                ),
                $activeKeys,
                true,
            ))
            ->each(fn (Notification $notification) => $notification->markAsDismissed())
            ->count();

        return [
            'created' => $created,
            'updated' => $updated,
            'dismissed' => $dismissed,
            'eligible_certificates' => $certificates->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSystemNotificationPayload(SertifikatSistemSemen $certificate): array
    {
        $action = $certificate->followUpAction();
        $targetDate = $this->systemFollowUpTargetDate($certificate);

        return [
            'title' => 'Tindak lanjut sertifikat sistem ISO',
            'message' => sprintf(
                '%s %s di %s membutuhkan tindak lanjut %s paling lambat %s.',
                $certificate->isoStandard?->code ?? 'ISO',
                $certificate->isoStandard?->name ?? 'Sistem Manajemen',
                $certificate->lokasiPabrik?->nama_lokasi ?? 'Lokasi Pabrik',
                $certificate->auditStageLabel(),
                $targetDate?->format('d M Y') ?? '-',
            ),
            'notification_type' => self::SYSTEM_NOTIFICATION_TYPE,
            'status' => NotificationStatus::Unread,
            'scheduled_at' => now(),
            'sent_at' => now(),
            'data' => [
                'certificate_type' => 'system',
                'certificate_id' => $certificate->id,
                'iso_code' => $certificate->isoStandard?->code,
                'iso_name' => $certificate->isoStandard?->name,
                'location' => $certificate->lokasiPabrik?->nama_lokasi,
                'follow_up_action' => $action,
                'follow_up_label' => $certificate->followUpActionLabel(),
                'audit_stage' => $certificate->auditStageLabel(),
                'action_url' => route('cement.system-follow-up.confirm', ['certificate' => $certificate, 'action' => $action]),
                'target_date' => $targetDate?->toDateString(),
                'target_date_label' => $targetDate?->format('d M Y'),
                'issued_at' => $certificate->issued_at?->toDateString(),
                'issued_at_label' => $certificate->issued_at?->format('d M Y'),
                'expires_at' => $certificate->berlaku_sd?->toDateString(),
                'expires_at_label' => $certificate->berlaku_sd?->format('d M Y'),
                'status_label' => $certificate->statusLabel(),
                'certificate_number' => $certificate->certificate_number,
            ],
        ];
    }

    private function systemWarningDays(): int
    {
        return max(90, max($this->warningDays()));
    }

    private function systemFollowUpTargetDate(SertifikatSistemSemen $certificate)
    {
        return $certificate->auditEvents
            ->firstWhere('audit_type', $certificate->followUpAction())
            ?->target_date
            ?? $certificate->followUpTargetDate();
    }

    /**
     * @return list<int>
     */
    private function warningDays(): array
    {
        $value = NotificationSetting::query()->where('key', 'expiry_warning_days')->value('value') ?: '90,60,30,7';

        return collect(explode(',', $value))
            ->map(fn ($day) => (int) trim($day))
            ->filter(fn (int $day) => $day > 0)
            ->values()
            ->all() ?: [90, 60, 30, 7];
    }

    private function systemNotificationKey(int $userId, int $certificateId, string $action): string
    {
        return $userId.'-'.$certificateId.'-'.$action;
    }
}
