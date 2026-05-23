<?php

namespace App\Services;

use App\Models\IsoStandard;
use App\Models\MerekSemen;
use App\Models\SertifikatGreenLabel;
use App\Models\SertifikatSistemSemen;
use App\Models\SertifikatSni;
use App\Models\SertifikatTkdn;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LandingPageSummaryService
{
    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        if (app()->runningUnitTests()) {
            return $this->buildFresh();
        }

        return Cache::remember('landing-page-summary.v2', now()->addMinute(), fn () => $this->buildFresh());
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFresh(): array
    {
        $sni = SertifikatSni::query()->with('merekSemen')->get();
        $tkdn = SertifikatTkdn::query()->with('merekSemen')->get();
        $greenLabel = SertifikatGreenLabel::query()->with('merekSemen')->get();
        $systemIso = SertifikatSistemSemen::query()->with(['isoStandard', 'lokasiPabrik'])->get();
        $allCertificates = collect()
            ->merge($this->tagCertificates($sni, 'SNI'))
            ->merge($this->tagCertificates($tkdn, 'TKDN'))
            ->merge($this->tagCertificates($greenLabel, 'Green Label'))
            ->merge($this->tagCertificates($systemIso, 'Sistem ISO'));

        $statusCounts = $allCertificates->countBy(fn (array $item) => $item['certificate']->statusKey());
        $totalCertificates = $allCertificates->count();
        $documentsReady = $allCertificates->filter(fn (array $item) => filled($item['certificate']->file_sertifikat))->count();

        $totals = [
            'brands' => MerekSemen::query()->count(),
            'sni' => $sni->count(),
            'tkdn' => $tkdn->count(),
            'green_label' => $greenLabel->count(),
            'iso_standards' => IsoStandard::query()->count(),
            'system_iso' => $systemIso->count(),
            'certificates' => $totalCertificates,
            'active' => (int) ($statusCounts['aktif'] ?? 0),
            'expiring_soon' => (int) ($statusCounts['akan_berakhir'] ?? 0),
            'expired' => (int) ($statusCounts['kadaluarsa'] ?? 0),
            'documents_ready' => $documentsReady,
        ];

        return [
            'totals' => $totals,
            'summaryStats' => $this->buildSummaryStats($totals),
            'statusDistribution' => $this->buildStatusDistribution($totals),
            'followUps' => $this->buildFollowUps($totals),
            'typeBreakdown' => $this->buildTypeBreakdown($sni, $tkdn, $greenLabel, $systemIso),
            'systemIsoHighlights' => $this->buildSystemIsoHighlights($systemIso),
            'publicSystemIso' => $this->buildPublicSystemIso($systemIso),
            'priorityCertificates' => $this->buildPriorityCertificates($allCertificates),
            'recentDocuments' => $this->buildRecentDocuments($allCertificates),
            'hasCertificates' => $totalCertificates > 0,
        ];
    }

    private function buildSummaryStats(array $totals): array
    {
        return [
            ['label' => 'Total Merek', 'value' => $totals['brands'], 'meta' => 'Merek semen yang masuk dalam database monitoring.', 'tone' => 'neutral'],
            ['label' => 'Sertifikat SNI', 'value' => $totals['sni'], 'meta' => 'Dokumen SNI produk semen yang sedang dipantau.', 'tone' => 'info'],
            ['label' => 'Sertifikat TKDN', 'value' => $totals['tkdn'], 'meta' => 'Data TKDN semen untuk kebutuhan monitoring.', 'tone' => 'warning'],
            ['label' => 'Green Label', 'value' => $totals['green_label'], 'meta' => 'Sertifikat Green Label semen yang tersimpan.', 'tone' => 'success'],
            ['label' => 'Sistem ISO', 'value' => $totals['system_iso'], 'meta' => 'Sertifikat ISO sistem manajemen pabrik semen.', 'tone' => 'info'],
            ['label' => 'Akan Berakhir', 'value' => $totals['expiring_soon'], 'meta' => 'Sertifikat dengan masa berlaku 90 hari ke depan.', 'tone' => 'warning'],
            ['label' => 'Kadaluarsa', 'value' => $totals['expired'], 'meta' => 'Sertifikat yang sudah melewati masa berlaku.', 'tone' => 'danger'],
        ];
    }

    private function buildStatusDistribution(array $totals): array
    {
        $totalCertificates = max($totals['certificates'], 1);

        return [
            ['label' => 'Aktif', 'value' => $totals['active'], 'width' => round(($totals['active'] / $totalCertificates) * 100, 2), 'tone' => 'success', 'note' => 'Masa berlaku masih lebih dari 90 hari.'],
            ['label' => 'Akan Berakhir', 'value' => $totals['expiring_soon'], 'width' => round(($totals['expiring_soon'] / $totalCertificates) * 100, 2), 'tone' => 'warning', 'note' => 'Perlu dipantau karena mendekati batas berlaku.'],
            ['label' => 'Kadaluarsa', 'value' => $totals['expired'], 'width' => round(($totals['expired'] / $totalCertificates) * 100, 2), 'tone' => 'danger', 'note' => 'Perlu pembaruan atau tindak lanjut dokumen.'],
        ];
    }

    private function buildFollowUps(array $totals): array
    {
        $maxValue = max($totals['sni'], $totals['tkdn'], $totals['green_label'], $totals['system_iso'], $totals['documents_ready'], 1);
        $width = static fn (int $value): float => round(($value / $maxValue) * 100, 2);

        return [
            ['label' => 'SNI', 'value' => $totals['sni'], 'width' => $width($totals['sni']), 'tone' => 'info', 'note' => 'Sertifikat SNI yang menjadi dasar pemantauan produk semen.'],
            ['label' => 'TKDN', 'value' => $totals['tkdn'], 'width' => $width($totals['tkdn']), 'tone' => 'warning', 'note' => 'Sertifikat TKDN untuk informasi kandungan dalam negeri.'],
            ['label' => 'Green Label', 'value' => $totals['green_label'], 'width' => $width($totals['green_label']), 'tone' => 'success', 'note' => 'Dokumen Green Label untuk aspek keberlanjutan produk.'],
            ['label' => 'Sistem ISO', 'value' => $totals['system_iso'], 'width' => $width($totals['system_iso']), 'tone' => 'info', 'note' => 'ISO sistem manajemen yang melekat ke lokasi/pabrik semen.'],
            ['label' => 'File Terunggah', 'value' => $totals['documents_ready'], 'width' => $width($totals['documents_ready']), 'tone' => 'neutral', 'note' => 'Sertifikat yang sudah memiliki file digital.'],
        ];
    }

    private function buildTypeBreakdown(Collection $sni, Collection $tkdn, Collection $greenLabel, Collection $systemIso): array
    {
        $types = collect([
            ['name' => 'Sertifikat SNI', 'count' => $sni->count(), 'tone' => 'info'],
            ['name' => 'Sertifikat TKDN', 'count' => $tkdn->count(), 'tone' => 'warning'],
            ['name' => 'Green Label', 'count' => $greenLabel->count(), 'tone' => 'success'],
            ['name' => 'Sistem ISO Semen', 'count' => $systemIso->count(), 'tone' => 'info'],
        ]);
        $total = max($types->sum('count'), 1);

        return $types
            ->map(fn (array $type) => [
                ...$type,
                'width' => round(($type['count'] / $total) * 100, 2),
                'share' => round(($type['count'] / $total) * 100).'%',
            ])
            ->all();
    }

    private function buildSystemIsoHighlights(Collection $systemIso): array
    {
        return $systemIso
            ->sortBy(fn (SertifikatSistemSemen $certificate) => [
                $certificate->isoStandard?->sort_order ?? 999,
                $certificate->berlaku_sd?->timestamp ?? PHP_INT_MAX,
            ])
            ->take(6)
            ->map(fn (SertifikatSistemSemen $certificate) => [
                'code' => $certificate->isoStandard?->code ?? 'ISO Semen',
                'name' => $certificate->isoStandard?->name ?? 'Sistem ISO Semen',
                'location' => $certificate->lokasiPabrik?->nama_lokasi ?? 'Lokasi Pabrik',
                'stage' => $certificate->auditStageLabel(),
                'status' => $certificate->statusLabel(),
                'tone' => match ($certificate->statusKey()) {
                    'kadaluarsa' => 'danger',
                    'akan_berakhir' => 'warning',
                    default => 'success',
                },
            ])
            ->values()
            ->all();
    }

    private function buildPublicSystemIso(Collection $systemIso): array
    {
        return $systemIso
            ->sortBy(fn (SertifikatSistemSemen $certificate) => [
                $certificate->statusKey() === 'aktif' ? 0 : 1,
                $certificate->isoStandard?->sort_order ?? 999,
                $certificate->lokasiPabrik?->nama_lokasi ?? '',
                $certificate->berlaku_sd?->timestamp ?? PHP_INT_MAX,
            ])
            ->take(8)
            ->map(fn (SertifikatSistemSemen $certificate) => [
                'code' => $certificate->isoStandard?->code ?? 'ISO',
                'name' => $certificate->isoStandard?->name ?? 'Sistem Manajemen',
                'location' => $certificate->lokasiPabrik?->nama_lokasi ?? 'Lokasi Pabrik',
                'issuer' => $certificate->issuer ?: '-',
                'scope' => $certificate->scope ?: '-',
                'category' => $certificate->certification_category ?: $certificate->isoStandard?->name,
                'acquisition_year' => $certificate->acquisition_year ?: $certificate->issued_at?->format('Y'),
                'level' => $certificate->certificationLevelLabel(),
                'valid_until' => $certificate->berlaku_sd?->format('d M Y') ?? '-',
                'status' => $certificate->statusLabel(),
                'tone' => match ($certificate->statusKey()) {
                    'kadaluarsa' => 'danger',
                    'akan_berakhir' => 'warning',
                    default => 'success',
                },
            ])
            ->values()
            ->all();
    }

    private function buildPriorityCertificates(Collection $certificates): array
    {
        return $certificates
            ->filter(fn (array $item) => $item['certificate']->berlaku_sd?->lte(today()->addDays(90)))
            ->sortBy(fn (array $item) => $item['certificate']->berlaku_sd)
            ->take(3)
            ->map(fn (array $item) => [
                'title' => $this->certificateTitle($item),
                'meta' => $item['certificate']->statusLabel().' - '.$item['certificate']->berlaku_sd?->format('d M Y'),
                'note' => $this->certificateNote($item),
                'is_system_iso' => $item['certificate'] instanceof SertifikatSistemSemen,
                'status' => $item['certificate']->statusLabel(),
                'valid_until' => $item['certificate']->berlaku_sd?->format('d M Y'),
                'tone' => match ($item['certificate']->statusKey()) {
                    'kadaluarsa' => 'danger',
                    'akan_berakhir' => 'warning',
                    default => 'info',
                },
            ])
            ->values()
            ->all();
    }

    private function buildRecentDocuments(Collection $certificates): array
    {
        return $certificates
            ->filter(fn (array $item) => filled($item['certificate']->file_sertifikat))
            ->sortByDesc(fn (array $item) => $item['certificate']->updated_at)
            ->take(3)
            ->map(fn (array $item) => [
                'title' => $item['type'],
                'meta' => $this->certificateOwner($item),
                'note' => 'Dokumen diperbarui '.$item['certificate']->updated_at?->diffForHumans(),
                'is_system_iso' => $item['certificate'] instanceof SertifikatSistemSemen,
                'tone' => 'success',
            ])
            ->values()
            ->all();
    }

    private function tagCertificates(Collection $certificates, string $type): Collection
    {
        return $certificates->map(fn ($certificate) => [
            'type' => $type,
            'certificate' => $certificate,
        ]);
    }

    private function certificateTitle(array $item): string
    {
        if ($item['certificate'] instanceof SertifikatSistemSemen) {
            return $item['type'].' - '.($item['certificate']->isoStandard?->code ?? 'ISO Semen');
        }

        return $item['type'].' - '.($item['certificate']->merekSemen?->nama_merek ?? 'Merek Semen');
    }

    private function certificateOwner(array $item): string
    {
        if ($item['certificate'] instanceof SertifikatSistemSemen) {
            return $item['certificate']->lokasiPabrik?->nama_lokasi ?? 'Lokasi Pabrik';
        }

        return $item['certificate']->merekSemen?->nama_merek ?? 'Merek Semen';
    }

    private function certificateNote(array $item): string
    {
        if ($item['certificate'] instanceof SertifikatSistemSemen) {
            return ($item['certificate']->isoStandard?->name ?? 'Sistem ISO').' / '.($item['certificate']->scope ?: 'Produksi semen');
        }

        return $item['certificate']->sni.' / '.$item['certificate']->komoditi;
    }
}
