<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Models\SertifikatGreenLabel;
use App\Models\SertifikatSistemSemen;
use App\Models\SertifikatSni;
use App\Models\SertifikatTkdn;
use App\Services\AuditLogger;
use App\Services\CertificateFileStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CementCertificateDownloadController extends Controller
{
    public function __construct(
        private readonly CertificateFileStorage $files,
    ) {}

    public function __invoke(string $type, int $certificate): StreamedResponse|RedirectResponse
    {
        $model = $this->resolveCertificate($type, $certificate);

        if (! $model || blank($model->file_sertifikat)) {
            return back()->with('error', 'File sertifikat tidak ditemukan.');
        }

        if (! $this->files->exists($model->file_sertifikat)) {
            return back()->with('error', 'File sertifikat tidak ditemukan.');
        }

        app(AuditLogger::class)->log($type === 'system' ? 'cement_system_certificate_downloaded' : 'cement_certificate_downloaded', $model, $type === 'system' ? 'File sertifikat sistem ISO diunduh.' : 'File sertifikat semen diunduh.', null, [
            'certificate_type' => $type,
            'file_path' => $model->file_sertifikat,
        ]);

        return $this->files->download($model->file_sertifikat, $this->downloadFilename($type, $model));
    }

    private function resolveCertificate(string $type, int $id): ?Model
    {
        return match ($type) {
            'sni' => SertifikatSni::query()->find($id),
            'tkdn' => SertifikatTkdn::query()->find($id),
            'green-label' => SertifikatGreenLabel::query()->find($id),
            'system' => SertifikatSistemSemen::query()->with(['isoStandard', 'lokasiPabrik'])->find($id),
            default => null,
        };
    }

    private function downloadFilename(string $type, Model $certificate): string
    {
        $extension = pathinfo((string) $certificate->file_sertifikat, PATHINFO_EXTENSION) ?: 'pdf';

        if ($certificate instanceof SertifikatSistemSemen) {
            $iso = Str::slug((string) $certificate->isoStandard?->code);
            $location = Str::slug((string) $certificate->lokasiPabrik?->nama_lokasi);

            return sprintf('sertifikat-sistem-%s-%s.%s', $iso ?: $certificate->getKey(), $location ?: 'semen', $extension);
        }

        $brand = Str::slug((string) $certificate->merekSemen?->nama_merek);
        $sni = Str::slug((string) $certificate->sni);

        return sprintf('sertifikat-%s-%s-%s.%s', $type, $brand ?: $certificate->getKey(), $sni ?: 'dokumen', $extension);
    }
}
