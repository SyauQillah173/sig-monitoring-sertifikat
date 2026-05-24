<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\SertifikatSistemSemen;
use App\Services\AuditLogger;
use App\Services\CertificateFileStorage;
use App\Services\SystemCertificateAuditTimelineService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SystemCertificateFollowUpController extends Controller
{
    public function __construct(
        private readonly SystemCertificateAuditTimelineService $auditTimeline,
        private readonly CertificateFileStorage $files,
    ) {}

    public function confirm(SertifikatSistemSemen $certificate, string $action): View
    {
        $this->authorizeAction($certificate, $action);

        return view('cement.system-follow-up.confirm', [
            'certificate' => $certificate->load(['isoStandard', 'lokasiPabrik']),
            'action' => $action,
            'isRenewal' => $action === SertifikatSistemSemen::AUDIT_STAGE_RENEWAL,
        ]);
    }

    public function store(Request $request, SertifikatSistemSemen $certificate, string $action): RedirectResponse
    {
        $this->authorizeAction($certificate, $action);

        if ($action === SertifikatSistemSemen::AUDIT_STAGE_RENEWAL) {
            return $this->storeRenewal($request, $certificate, $action);
        }

        return $this->completeSurveillance($request, $certificate, $action);
    }

    private function completeSurveillance(Request $request, SertifikatSistemSemen $certificate, string $action): RedirectResponse
    {
        $payload = $request->validate([
            'completed_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'evidence_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$this->files->maxUploadKilobytes()],
        ]);

        $payload['evidence_file'] = $this->files->store($request->file('evidence_file'), 'uploads/sertifikat-sistem-audit');

        $oldValues = $certificate->only(['audit_stage', 'notes']);
        $label = $certificate->auditStageLabel();
        $notes = trim((string) ($payload['notes'] ?? ''));
        $auditNote = 'Tindak lanjut '.$label.' diselesaikan pada '.$payload['completed_at'].'.';
        if ($notes !== '') {
            $auditNote .= ' Catatan: '.$notes;
        }

        $certificate->update([
            'audit_stage' => $certificate->nextAuditStageAfterFollowUp(),
            'notes' => trim(implode("\n\n", array_filter([$certificate->notes, $auditNote]))),
        ]);
        $this->auditTimeline->completeCurrentEvent($certificate, $action, $payload);
        $this->auditTimeline->markNextPending($certificate->fresh());

        $this->markRelatedNotificationsAsRead($certificate, $action);

        app(AuditLogger::class)->log('cement_system_follow_up_completed', $certificate, $auditNote, $oldValues, [
            'audit_stage' => $certificate->audit_stage,
            'completed_at' => $payload['completed_at'],
            'follow_up_action' => $action,
        ]);

        return redirect()
            ->route('notifications.index')
            ->with('success', $label.' berhasil dikonfirmasi. Tahap audit diperbarui ke '.$certificate->auditStageLabel().'.');
    }

    private function storeRenewal(Request $request, SertifikatSistemSemen $certificate, string $action): RedirectResponse
    {
        $payload = $request->validate([
            'certificate_number' => ['required', 'string', 'max:255', Rule::unique('sertifikat_sistem_semen', 'certificate_number')],
            'issuer' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['required', 'date'],
            'berlaku_sd' => ['required', 'date', 'after_or_equal:issued_at'],
            'file_sertifikat' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$this->files->maxUploadKilobytes()],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $filePath = $this->files->store($request->file('file_sertifikat'), 'uploads/sertifikat-sistem');

        $newCertificate = SertifikatSistemSemen::query()->create([
            'lokasi_pabrik_id' => $certificate->lokasi_pabrik_id,
            'iso_standard_id' => $certificate->iso_standard_id,
            'certificate_number' => $payload['certificate_number'],
            'issuer' => $payload['issuer'] ?? $certificate->issuer,
            'audit_stage' => SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_1,
            'scope' => $certificate->scope,
            'issued_at' => $payload['issued_at'],
            'berlaku_sd' => $payload['berlaku_sd'],
            'acquisition_year' => (int) date('Y', strtotime($payload['issued_at'])),
            'certification_level' => $certificate->certification_level,
            'certification_category' => $certificate->certification_category,
            'process_owner' => $certificate->process_owner,
            'accreditation_number' => $certificate->accreditation_number,
            'public_url' => $certificate->public_url,
            'description' => $certificate->description,
            'file_sertifikat' => $filePath,
            'notes' => $payload['notes'] ?? null,
        ]);
        $this->auditTimeline->completeCurrentEvent($certificate, $action, [
            'completed_at' => $payload['issued_at'],
            'notes' => $payload['notes'] ?? 'Renewal selesai dengan sertifikat baru '.$newCertificate->certificate_number.'.',
        ]);
        $this->auditTimeline->syncFor($newCertificate);

        $this->markRelatedNotificationsAsRead($certificate, $action);

        app(AuditLogger::class)->log('cement_system_renewal_created', $newCertificate, 'Renewal sertifikat sistem ISO dibuat dari notifikasi tindak lanjut.', [
            'source_certificate_id' => $certificate->id,
            'source_certificate_number' => $certificate->certificate_number,
        ], [
            'certificate_number' => $newCertificate->certificate_number,
            'issued_at' => $newCertificate->issued_at?->toDateString(),
            'berlaku_sd' => $newCertificate->berlaku_sd?->toDateString(),
        ]);

        return redirect()
            ->route('notifications.index')
            ->with('success', 'Data renewal sertifikat sistem ISO berhasil dibuat.');
    }

    private function authorizeAction(SertifikatSistemSemen $certificate, string $action): void
    {
        abort_unless(in_array($action, array_keys(SertifikatSistemSemen::auditStageOptions()), true), 404);
        abort_unless($certificate->followUpAction() === $action, 422, 'Aksi tindak lanjut tidak sesuai dengan tahap sertifikat saat ini.');
    }

    private function markRelatedNotificationsAsRead(SertifikatSistemSemen $certificate, string $action): void
    {
        Notification::query()
            ->where('notification_type', 'cement_system_follow_up')
            ->get()
            ->filter(fn (Notification $notification) => (int) data_get($notification->data, 'certificate_id') === $certificate->id
                && data_get($notification->data, 'follow_up_action') === $action)
            ->each(fn (Notification $notification) => $notification->markAsRead());
    }
}
