<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use App\Models\SertifikatGreenLabel;
use App\Models\SertifikatSistemSemen;
use App\Models\SertifikatSni;
use App\Models\SertifikatTkdn;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CementCertificateDocumentController extends Controller
{
    private const DEFAULT_TEMPLATE = 'images/Sertifikat.jpg';

    public function __invoke(string $type, int $certificate): Response|RedirectResponse
    {
        $model = $this->resolveCertificate($type, $certificate);

        if (! $model) {
            return back()->with('error', 'Data sertifikat tidak ditemukan.');
        }

        app(AuditLogger::class)->log('cement_certificate_summary_document_downloaded', $model, 'Dokumen ringkasan sertifikat semen diunduh.', null, [
            'certificate_type' => $type,
        ]);

        return Pdf::loadView('cement.certificates.document', [
            'certificate' => $model,
            'type' => $type,
            'document' => $this->documentPayload($type, $model),
        ])
            ->setPaper('a4', 'portrait')
            ->download($this->filename($type, $model));
    }

    private function resolveCertificate(string $type, int $id): ?Model
    {
        return match ($type) {
            'sni' => SertifikatSni::query()->with(['merekSemen.kategoriSemen', 'lokasiPabrik'])->find($id),
            'tkdn' => SertifikatTkdn::query()->with(['merekSemen.kategoriSemen', 'lokasiPabrik'])->find($id),
            'green-label' => SertifikatGreenLabel::query()->with(['merekSemen.kategoriSemen', 'lokasiPabrik'])->find($id),
            'system' => SertifikatSistemSemen::query()->with(['isoStandard', 'lokasiPabrik'])->find($id),
            default => null,
        };
    }

    private function documentPayload(string $type, Model $certificate): array
    {
        if ($certificate instanceof SertifikatSistemSemen) {
            return [
                'title' => 'Dokumen Ringkasan Sertifikat Sistem',
                'subtitle' => 'Sistem Manajemen ISO Semen',
                'number' => $certificate->certificate_number ?: 'SIG-SYS-'.$certificate->getKey(),
                'owner' => $certificate->lokasiPabrik?->nama_lokasi ?? 'Pabrik/Lokasi Semen',
                'category' => $certificate->isoStandard?->code,
                'status' => $certificate->statusLabel(),
                'valid_until' => $certificate->berlaku_sd?->format('d M Y'),
                'template_path' => $this->templatePath(),
                'rows' => [
                    ['Standar ISO', trim(($certificate->isoStandard?->code ?? '').' - '.($certificate->isoStandard?->name ?? ''))],
                    ['Nomor Sertifikat', $certificate->certificate_number ?: '-'],
                    ['Lembaga Sertifikasi', $certificate->issuer ?: '-'],
                    ['Lokasi/Pabrik', $certificate->lokasiPabrik?->nama_lokasi ?? '-'],
                    ['Tahun Perolehan', $certificate->acquisition_year ?: '-'],
                    ['Nasional/Internasional', $certificate->certificationLevelLabel()],
                    ['Kategori Sertifikasi', $certificate->certification_category ?: '-'],
                    ['Pemilik Proses', $certificate->process_owner ?: '-'],
                    ['Nomor Akreditasi', $certificate->accreditation_number ?: '-'],
                    ['Tahap Audit', $certificate->auditStageLabel()],
                    ['Scope/Cakupan', $certificate->scope ?: '-'],
                    ['Deskripsi', $certificate->description ?: '-'],
                    ['Tanggal Terbit', $certificate->issued_at?->format('d M Y') ?? '-'],
                    ['Berlaku Sampai', $certificate->berlaku_sd?->format('d M Y') ?? '-'],
                    ['Status Monitoring', $certificate->statusLabel()],
                    ['Catatan', $certificate->notes ?: '-'],
                ],
            ];
        }

        $baseRows = [
            ['Kategori Produk', $certificate->merekSemen?->kategoriSemen?->nama_kategori ?? '-'],
            ['Merek Semen', $certificate->merekSemen?->nama_merek ?? '-'],
            ['Nomor/Standar SNI', $certificate->sni ?? '-'],
            ['Komoditi', $certificate->komoditi ?? '-'],
            ['Lokasi/Pabrik', $certificate->lokasi ?: ($certificate->lokasiPabrik?->nama_lokasi ?? '-')],
        ];

        $specificRows = match ($type) {
            'tkdn' => [
                ['Persentase TKDN', number_format((float) $certificate->persentase_tkdn, 2, ',', '.').'%'],
                ['Kemasan', $certificate->kemasan ?: '-'],
            ],
            'green-label' => [
                ['Peringkat Green Label', $certificate->peringkat ?: '-'],
            ],
            default => [
                ['Jenis Sertifikasi', $certificate->jenis_sertifikasi ?: '-'],
                ['LSPro', $certificate->lspro ?: '-'],
            ],
        };

        return [
            'title' => 'Dokumen Ringkasan Sertifikat Produk',
            'subtitle' => match ($type) {
                'tkdn' => 'Sertifikat TKDN Semen',
                'green-label' => 'Sertifikat Green Label Semen',
                default => 'Sertifikat SNI Semen',
            },
            'number' => 'SIG-'.Str::upper(Str::slug($type, '')).'-'.$certificate->getKey(),
            'owner' => $certificate->merekSemen?->nama_merek ?? 'Produk Semen',
            'category' => $certificate->merekSemen?->kategoriSemen?->nama_kategori ?? 'Semen',
            'status' => $certificate->statusLabel(),
            'valid_until' => $certificate->berlaku_sd?->format('d M Y'),
            'template_path' => $this->templatePath(),
            'rows' => [
                ...$baseRows,
                ...$specificRows,
                ['Berlaku Sampai', $certificate->berlaku_sd?->format('d M Y') ?? '-'],
                ['Status Monitoring', $certificate->statusLabel()],
                ['ID Sertifikat Sistem', '#'.$certificate->getKey()],
            ],
        ];
    }

    private function filename(string $type, Model $certificate): string
    {
        $subject = $certificate instanceof SertifikatSistemSemen
            ? ($certificate->isoStandard?->code.' '.$certificate->lokasiPabrik?->nama_lokasi)
            : ($certificate->merekSemen?->nama_merek.' '.$certificate->sni);

        return 'dokumen-ringkasan-sertifikat-'.Str::slug($type.' '.$subject.' '.$certificate->getKey()).'.pdf';
    }

    private function templatePath(): string
    {
        $path = NotificationSetting::query()->where('key', 'certificate_template_path')->value('value') ?: self::DEFAULT_TEMPLATE;

        return File::exists(public_path($path)) ? $path : self::DEFAULT_TEMPLATE;
    }
}
