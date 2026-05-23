<?php

namespace App\Services;

use App\Models\SertifikatSistemAuditEvent;
use App\Models\SertifikatSistemSemen;
use Carbon\CarbonInterface;

class SystemCertificateAuditTimelineService
{
    public function syncFor(SertifikatSistemSemen $certificate): void
    {
        if (! $certificate->issued_at || ! $certificate->berlaku_sd) {
            return;
        }

        foreach ($this->eventsFor($certificate) as $event) {
            $auditEvent = $certificate->auditEvents()->updateOrCreate(
                ['audit_type' => $event['audit_type']],
                [
                    'target_date' => $event['target_date'],
                    'status' => $event['status'],
                ],
            );

            if ($event['status'] === SertifikatSistemAuditEvent::STATUS_COMPLETED && ! $auditEvent->completed_at) {
                $auditEvent->update(['completed_at' => $event['target_date']]);
            }
        }
    }

    public function completeCurrentEvent(SertifikatSistemSemen $certificate, string $action, array $payload): ?SertifikatSistemAuditEvent
    {
        $this->syncFor($certificate);

        $event = $certificate->auditEvents()
            ->where('audit_type', $action)
            ->first();

        if (! $event) {
            return null;
        }

        $event->update([
            'user_id' => auth()->id(),
            'completed_at' => $payload['completed_at'] ?? now()->toDateString(),
            'status' => SertifikatSistemAuditEvent::STATUS_COMPLETED,
            'evidence_file' => $payload['evidence_file'] ?? $event->evidence_file,
            'notes' => $payload['notes'] ?? $event->notes,
        ]);

        return $event;
    }

    public function markNextPending(SertifikatSistemSemen $certificate): void
    {
        $this->syncFor($certificate);

        $certificate->auditEvents()
            ->where('audit_type', $certificate->followUpAction())
            ->update(['status' => SertifikatSistemAuditEvent::STATUS_PENDING]);
    }

    /**
     * @return array<int, array{audit_type: string, target_date: string, status: string}>
     */
    private function eventsFor(SertifikatSistemSemen $certificate): array
    {
        $issuedAt = $certificate->issued_at->copy()->startOfDay();
        $expiresAt = $certificate->berlaku_sd->copy()->startOfDay();
        $currentStage = $certificate->followUpAction();

        return collect([
            [SertifikatSistemAuditEvent::TYPE_INITIAL, $issuedAt, SertifikatSistemAuditEvent::STATUS_COMPLETED],
            [SertifikatSistemAuditEvent::TYPE_SURVEILEN_1, $this->targetDate($issuedAt, $expiresAt, 1), $this->statusFor(SertifikatSistemAuditEvent::TYPE_SURVEILEN_1, $currentStage)],
            [SertifikatSistemAuditEvent::TYPE_SURVEILEN_2, $this->targetDate($issuedAt, $expiresAt, 2), $this->statusFor(SertifikatSistemAuditEvent::TYPE_SURVEILEN_2, $currentStage)],
            [SertifikatSistemAuditEvent::TYPE_RENEWAL, $expiresAt, $this->statusFor(SertifikatSistemAuditEvent::TYPE_RENEWAL, $currentStage)],
        ])
            ->map(fn (array $event) => [
                'audit_type' => $event[0],
                'target_date' => $event[1]->toDateString(),
                'status' => $event[2],
            ])
            ->all();
    }

    private function statusFor(string $type, string $currentStage): string
    {
        $order = [
            SertifikatSistemAuditEvent::TYPE_SURVEILEN_1 => 1,
            SertifikatSistemAuditEvent::TYPE_SURVEILEN_2 => 2,
            SertifikatSistemAuditEvent::TYPE_RENEWAL => 3,
        ];

        if (($order[$type] ?? 0) < ($order[$currentStage] ?? 0)) {
            return SertifikatSistemAuditEvent::STATUS_COMPLETED;
        }

        if ($type === $currentStage) {
            return SertifikatSistemAuditEvent::STATUS_PENDING;
        }

        return SertifikatSistemAuditEvent::STATUS_UPCOMING;
    }

    private function targetDate(CarbonInterface $issuedAt, CarbonInterface $expiresAt, int $years): CarbonInterface
    {
        $targetDate = $issuedAt->copy()->addYears($years);

        return $targetDate->gt($expiresAt) ? $expiresAt : $targetDate;
    }
}
