<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

class SystemSettingService
{
    /**
     * @return array<string, string>
     */
    public function publicLandingSettings(): array
    {
        if (app()->runningUnitTests()) {
            return $this->values('public_landing');
        }

        return Cache::remember('system-settings.public_landing.v1', now()->addMinutes(5), fn () => $this->values('public_landing'));
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function savePublicLandingSettings(array $values): void
    {
        $labels = $this->labels();
        $now = now();

        $rows = collect($this->publicLandingDefaults())
            ->map(fn (string $default, string $key): array => [
                'key' => $key,
                'value' => (string) ($values[$key] ?? $default),
                'group' => 'public_landing',
                'label' => $labels[$key] ?? $key,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        SystemSetting::query()->upsert(
            $rows,
            ['key'],
            ['value', 'group', 'label', 'updated_at'],
        );

        Cache::put(
            'system-settings.public_landing.v1',
            collect($rows)->pluck('value', 'key')->all(),
            now()->addMinutes(5),
        );
    }

    /**
     * @return array<string, string>
     */
    public function values(string $group): array
    {
        $defaults = match ($group) {
            'public_landing' => $this->publicLandingDefaults(),
            default => [],
        };

        try {
            $stored = SystemSetting::query()
                ->where('group', $group)
                ->pluck('value', 'key')
                ->all();
        } catch (QueryException) {
            return $defaults;
        }

        return array_replace($defaults, $stored);
    }

    /**
     * @return array<string, string>
     */
    private function publicLandingDefaults(): array
    {
        return [
            'public_brand_kicker' => 'Internal Monitoring Platform',
            'public_brand_name' => config('app.name', 'SIG Monitoring Sertifikat'),
            'landing_badge' => 'Sistem Monitoring Sertifikat Semen',
            'landing_title' => 'Monitoring sertifikat produk dan sistem ISO semen yang terpusat.',
            'landing_description' => 'Platform ini membantu organisasi mengendalikan masa berlaku SNI, TKDN, Green Label, dan ISO sistem manajemen semen, menata dokumen pendukung secara terpusat, dan menyajikan laporan monitoring dalam satu workspace profesional.',
            'landing_value_1_title' => 'Monitoring masa berlaku',
            'landing_value_1_body' => 'dengan status yang jelas dan mudah dibaca.',
            'landing_value_2_title' => 'Pengelolaan dokumen terpusat',
            'landing_value_2_body' => 'agar arsip sertifikat tetap tertata dan mudah ditelusuri.',
            'landing_value_3_title' => 'Ringkasan status dan laporan',
            'landing_value_3_body' => 'untuk kebutuhan evaluasi serta pengambilan keputusan.',
            'show_landing_summary_stats' => '1',
            'show_landing_status_monitoring' => '1',
            'show_landing_document_composition' => '1',
            'show_landing_certificate_mix' => '1',
            'show_landing_public_iso' => '1',
            'show_landing_priority_feed' => '1',
            'show_public_iso_location' => '1',
            'show_public_iso_issuer' => '1',
            'show_public_iso_scope' => '1',
            'show_public_iso_validity' => '1',
            'show_public_iso_status' => '1',
            'show_public_iso_level_year' => '1',
            'show_public_iso_category' => '1',
            'footer_text' => 'Developer by Velly Cantik',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function labels(): array
    {
        return [
            'public_brand_kicker' => 'Brand Kicker',
            'public_brand_name' => 'Nama Brand',
            'landing_badge' => 'Badge Landing',
            'landing_title' => 'Judul Landing',
            'landing_description' => 'Deskripsi Landing',
            'landing_value_1_title' => 'Value 1 - Judul',
            'landing_value_1_body' => 'Value 1 - Isi',
            'landing_value_2_title' => 'Value 2 - Judul',
            'landing_value_2_body' => 'Value 2 - Isi',
            'landing_value_3_title' => 'Value 3 - Judul',
            'landing_value_3_body' => 'Value 3 - Isi',
            'show_landing_summary_stats' => 'Tampilkan Ringkasan Angka',
            'show_landing_status_monitoring' => 'Tampilkan Status Monitoring',
            'show_landing_document_composition' => 'Tampilkan Komposisi Dokumen',
            'show_landing_certificate_mix' => 'Tampilkan Distribusi Jenis Sertifikat',
            'show_landing_public_iso' => 'Tampilkan Sertifikasi Sistem ISO',
            'show_landing_priority_feed' => 'Tampilkan Prioritas Operasional',
            'show_public_iso_location' => 'ISO Publik - Lokasi',
            'show_public_iso_issuer' => 'ISO Publik - Lembaga',
            'show_public_iso_scope' => 'ISO Publik - Scope',
            'show_public_iso_validity' => 'ISO Publik - Masa Berlaku',
            'show_public_iso_status' => 'ISO Publik - Status',
            'show_public_iso_level_year' => 'ISO Publik - Tahun dan Level',
            'show_public_iso_category' => 'ISO Publik - Kategori',
            'footer_text' => 'Footer',
        ];
    }
}
