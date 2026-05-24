<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Notification;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DashboardMonitoringService
{
    /**
     * @return array{
     *     highlights: list<array{label: string, value: int, state: string}>,
     *     chart: list<array{label: string, value: int, color: string, width: float}>,
     *     expiringCertificates: Collection<int, Certificate>,
     *     focusMode: string,
     *     operationalLinks: list<array{label: string, href: string, style: string}>,
     *     recentNotifications: Collection<int, Notification>,
     *     insights: list<array{title: string, copy: string, tone: string}>,
     *     summaryNote: string
     * }
     */
    public function build(UserRole $role, ?User $user = null): array
    {
        $productCount = Product::query()->count();
        $certificateCounts = Certificate::monitoringAggregateCounts();
        $expiringCertificates = Certificate::query()
            ->with(['product', 'certificateType', 'issuer'])
            ->upcomingExpiry()
            ->limit(5)
            ->get();
        $unreadNotificationsCount = $user?->unreadSystemNotifications()->count() ?? 0;

        return [
            'highlights' => [
                ['label' => 'Total Produk', 'value' => $productCount, 'state' => 'default'],
                ['label' => 'Sertifikat Aktif', 'value' => $certificateCounts['active'], 'state' => 'ready'],
                ['label' => 'Akan Habis', 'value' => $certificateCounts['expiring_soon'], 'state' => 'warning'],
                ['label' => 'Habis', 'value' => $certificateCounts['expired'], 'state' => 'critical'],
            ],
            'chart' => $this->buildChartData($certificateCounts),
            'expiringCertificates' => $expiringCertificates,
            'focusMode' => $role === UserRole::Petugas ? 'operational' : 'summary',
            'operationalLinks' => $role === UserRole::Petugas ? [
                ['label' => 'Tambah Sertifikat', 'href' => route('certificates.create'), 'style' => 'primary'],
                ['label' => 'Lihat Akan Habis', 'href' => route('certificates.index', ['status' => 'expiring_soon']), 'style' => 'secondary'],
                ['label' => 'Lihat Semua Sertifikat', 'href' => route('certificates.index'), 'style' => 'secondary'],
            ] : [],
            'recentNotifications' => $user?->systemNotifications()
                ->with(['certificate.product'])
                ->visibleInInbox()
                ->latestFirst()
                ->limit(4)
                ->get() ?? new Collection,
            'insights' => $this->buildInsights(
                $role,
                $productCount,
                $certificateCounts,
                $unreadNotificationsCount,
            ),
            'summaryNote' => 'Ringkasan ini membantu memantau kesehatan data sertifikat dan fokus tindak lanjut terdekat.',
        ];
    }

    /**
     * @param  array{active: int, expiring_soon: int, expired: int}  $certificateCounts
     * @return list<array{title: string, copy: string, tone: string}>
     */
    private function buildInsights(
        UserRole $role,
        int $productCount,
        array $certificateCounts,
        int $unreadNotificationsCount,
    ): array {
        $attentionCount = $certificateCounts['expiring_soon'] + $certificateCounts['expired'];

        $insights = [
            $certificateCounts['expired'] > 0
                ? [
                    'title' => "{$certificateCounts['expired']} sertifikat sudah habis masa berlaku.",
                    'copy' => 'Prioritaskan tindak lanjut pada dokumen yang sudah melewati masa berlaku agar tidak mengganggu operasional.',
                    'tone' => 'danger',
                ]
                : ($certificateCounts['expiring_soon'] > 0
                    ? [
                        'title' => "{$certificateCounts['expiring_soon']} sertifikat mendekati masa habis.",
                        'copy' => 'Siapkan pembaruan dokumen lebih awal untuk menekan risiko keterlambatan perpanjangan.',
                        'tone' => 'warning',
                    ]
                    : [
                        'title' => 'Tidak ada sertifikat kritis saat ini.',
                        'copy' => 'Status monitoring masih terkendali dan belum ada item yang membutuhkan eskalasi segera.',
                        'tone' => 'success',
                    ]),
        ];

        $insights[] = match ($role) {
            UserRole::Admin => [
                'title' => $productCount > 0
                    ? "Monitoring saat ini mencakup {$productCount} produk."
                    : 'Master data produk masih kosong.',
                'copy' => $productCount > 0
                    ? 'Pastikan kualitas data tetap terjaga agar monitoring, notifikasi, dan laporan berjalan konsisten.'
                    : 'Tambahkan master data produk agar dashboard, monitoring, dan laporan mulai terisi secara operasional.',
                'tone' => $productCount > 0 ? 'info' : 'warning',
            ],
            UserRole::Petugas => [
                'title' => $attentionCount > 0
                    ? "{$attentionCount} item membutuhkan perhatian operasional."
                    : 'Belum ada antrean tindak lanjut yang kritis.',
                'copy' => $attentionCount > 0
                    ? 'Mulai dari sertifikat yang habis lalu lanjutkan ke dokumen yang mendekati batas berlaku.'
                    : 'Anda bisa melanjutkan input data baru atau pemeriksaan dokumen secara bertahap.',
                'tone' => $attentionCount > 0 ? 'warning' : 'info',
            ],
        };

        $insights[] = $unreadNotificationsCount > 0
            ? [
                'title' => "{$unreadNotificationsCount} notifikasi belum dibaca.",
                'copy' => 'Buka notifikasi internal untuk melihat item yang perlu perhatian atau tindak lanjut lebih lanjut.',
                'tone' => 'info',
            ]
            : [
                'title' => 'Tidak ada notifikasi baru.',
                'copy' => 'Dashboard akan menampilkan alert otomatis ketika ada masa berlaku yang perlu diperhatikan.',
                'tone' => 'info',
            ];

        return $insights;
    }

    /**
     * @param  array{active: int, expiring_soon: int, expired: int}  $certificateCounts
     * @return list<array{label: string, value: int, color: string, width: float}>
     */
    private function buildChartData(array $certificateCounts): array
    {
        $total = max(array_sum($certificateCounts), 1);

        return [
            [
                'label' => 'Aktif',
                'value' => $certificateCounts['active'],
                'color' => 'bg-emerald-500',
                'width' => round(($certificateCounts['active'] / $total) * 100, 2),
            ],
            [
                'label' => 'Akan Habis',
                'value' => $certificateCounts['expiring_soon'],
                'color' => 'bg-amber-500',
                'width' => round(($certificateCounts['expiring_soon'] / $total) * 100, 2),
            ],
            [
                'label' => 'Habis',
                'value' => $certificateCounts['expired'],
                'color' => 'bg-rose-500',
                'width' => round(($certificateCounts['expired'] / $total) * 100, 2),
            ],
        ];
    }
}
