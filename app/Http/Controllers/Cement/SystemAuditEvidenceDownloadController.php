<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Models\SertifikatSistemAuditEvent;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemAuditEvidenceDownloadController extends Controller
{
    public function __invoke(SertifikatSistemAuditEvent $auditEvent): StreamedResponse
    {
        abort_if(blank($auditEvent->evidence_file), 404);
        abort_unless(Storage::disk('local')->exists($auditEvent->evidence_file), 404);

        $auditEvent->loadMissing(['certificate.isoStandard', 'certificate.lokasiPabrik']);

        app(AuditLogger::class)->log('cement_system_audit_evidence_downloaded', $auditEvent, 'Bukti audit sertifikat sistem ISO diunduh.', null, [
            'certificate_id' => $auditEvent->sertifikat_sistem_semen_id,
            'iso_standard' => $auditEvent->certificate?->isoStandard?->code,
            'location' => $auditEvent->certificate?->lokasiPabrik?->nama_lokasi,
            'audit_type' => $auditEvent->audit_type,
            'file_path' => $auditEvent->evidence_file,
            'disk' => 'local',
        ]);

        return Storage::disk('local')->download($auditEvent->evidence_file);
    }
}
