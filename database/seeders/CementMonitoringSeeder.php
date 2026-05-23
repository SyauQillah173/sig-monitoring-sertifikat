<?php

namespace Database\Seeders;

use App\Models\CementReferenceValue;
use App\Models\IsoStandard;
use App\Models\KategoriSemen;
use App\Models\LokasiPabrik;
use App\Models\MerekSemen;
use App\Models\NotificationSetting;
use App\Models\SertifikatGreenLabel;
use App\Models\SertifikatSistemSemen;
use App\Models\SertifikatSni;
use App\Models\SertifikatTkdn;
use App\Services\SystemCertificateAuditTimelineService;
use Illuminate\Database\Seeder;

class CementMonitoringSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLocations();
        $brands = $this->seedCategoriesAndBrands();
        $this->seedReferences();
        $this->seedIsoStandards();
        $this->seedNotificationSettings();
        $this->seedSystemCertificates();

        foreach ($this->sniCertificates() as $payload) {
            $location = $this->locationByName($payload['lokasi']);
            $sni = $this->referenceByName(CementReferenceValue::TYPE_SNI, $payload['sni']);
            $komoditi = $this->referenceByName(CementReferenceValue::TYPE_KOMODITI, $payload['komoditi']);
            $jenisSertifikasi = $this->referenceByName(CementReferenceValue::TYPE_JENIS_SERTIFIKASI, $payload['jenis_sertifikasi']);
            $lspro = $this->referenceByName(CementReferenceValue::TYPE_LSPRO, $payload['lspro']);

            SertifikatSni::query()->updateOrCreate(
                [
                    'merek_semen_id' => $brands[$payload['kategori'].'|'.$payload['merek']]->id,
                    'sni' => $payload['sni'],
                    'komoditi' => $payload['komoditi'],
                    'lspro' => $payload['lspro'],
                    'berlaku_sd' => $payload['berlaku_sd'],
                ],
                [
                    'sni_reference_id' => $sni->id,
                    'komoditi_reference_id' => $komoditi->id,
                    'jenis_sertifikasi_reference_id' => $jenisSertifikasi->id,
                    'lspro_reference_id' => $lspro->id,
                    'lokasi_pabrik_id' => $location->id,
                    'jenis_sertifikasi' => $payload['jenis_sertifikasi'],
                    'lokasi' => $payload['lokasi'],
                ],
            );
        }

        foreach ($this->tkdnCertificates() as $payload) {
            $location = $this->locationByName($payload['lokasi']);
            $sni = $this->referenceByName(CementReferenceValue::TYPE_SNI, $payload['sni']);
            $komoditi = $this->referenceByName(CementReferenceValue::TYPE_KOMODITI, $payload['komoditi']);
            $kemasan = $this->referenceByName(CementReferenceValue::TYPE_KEMASAN, $payload['kemasan']);

            SertifikatTkdn::query()->updateOrCreate(
                [
                    'merek_semen_id' => $brands[$payload['kategori'].'|'.$payload['merek']]->id,
                    'sni' => $payload['sni'],
                    'kemasan' => $payload['kemasan'],
                    'berlaku_sd' => $payload['berlaku_sd'],
                ],
                [
                    'sni_reference_id' => $sni->id,
                    'komoditi_reference_id' => $komoditi->id,
                    'kemasan_reference_id' => $kemasan->id,
                    'lokasi_pabrik_id' => $location->id,
                    'komoditi' => $payload['komoditi'],
                    'persentase_tkdn' => $payload['persentase_tkdn'],
                    'lokasi' => $payload['lokasi'],
                ],
            );
        }

        foreach ($this->greenLabelCertificates() as $payload) {
            $location = $this->locationByName($payload['lokasi']);
            $sni = $this->referenceByName(CementReferenceValue::TYPE_SNI, $payload['sni']);
            $komoditi = $this->referenceByName(CementReferenceValue::TYPE_KOMODITI, $payload['komoditi']);
            $peringkat = $this->referenceByName(CementReferenceValue::TYPE_PERINGKAT_GREEN_LABEL, $payload['peringkat']);

            SertifikatGreenLabel::query()->updateOrCreate(
                [
                    'merek_semen_id' => $brands[$payload['kategori'].'|'.$payload['merek']]->id,
                    'sni' => $payload['sni'],
                    'berlaku_sd' => $payload['berlaku_sd'],
                ],
                [
                    'sni_reference_id' => $sni->id,
                    'komoditi_reference_id' => $komoditi->id,
                    'peringkat_green_label_reference_id' => $peringkat->id,
                    'lokasi_pabrik_id' => $location->id,
                    'komoditi' => $payload['komoditi'],
                    'peringkat' => $payload['peringkat'],
                    'lokasi' => $payload['lokasi'],
                ],
            );
        }
    }

    private function referenceByName(string $type, string $name): CementReferenceValue
    {
        return CementReferenceValue::query()
            ->where('type', $type)
            ->where('name', $name)
            ->firstOrFail();
    }

    private function locationByName(string $name): LokasiPabrik
    {
        return LokasiPabrik::query()->where('nama_lokasi', $name)->firstOrFail();
    }

    private function seedNotificationSettings(): void
    {
        $settings = [
            'internal_recipient_email' => 'abdullahsyauqillah02@gmail.com',
            'expiry_warning_days' => '90,60,30,7',
            'send_hour' => '7',
            'is_email_enabled' => '1',
        ];

        foreach ($settings as $key => $value) {
            NotificationSetting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }

    private function seedReferences(): void
    {
        $rows = [
            CementReferenceValue::TYPE_SNI => collect($this->sniCertificates())->pluck('sni')
                ->merge(collect($this->tkdnCertificates())->pluck('sni'))
                ->merge(collect($this->greenLabelCertificates())->pluck('sni'))
                ->unique()
                ->values()
                ->all(),
            CementReferenceValue::TYPE_KOMODITI => collect($this->sniCertificates())->pluck('komoditi')
                ->merge(collect($this->tkdnCertificates())->pluck('komoditi'))
                ->merge(collect($this->greenLabelCertificates())->pluck('komoditi'))
                ->unique()
                ->values()
                ->all(),
            CementReferenceValue::TYPE_LSPRO => collect($this->sniCertificates())->pluck('lspro')->unique()->values()->all(),
            CementReferenceValue::TYPE_JENIS_SERTIFIKASI => collect($this->sniCertificates())->pluck('jenis_sertifikasi')->unique()->values()->all(),
            CementReferenceValue::TYPE_KEMASAN => collect($this->tkdnCertificates())->pluck('kemasan')->unique()->values()->all(),
            CementReferenceValue::TYPE_PERINGKAT_GREEN_LABEL => collect($this->greenLabelCertificates())->pluck('peringkat')->unique()->values()->all(),
        ];

        foreach ($rows as $type => $names) {
            foreach ($names as $name) {
                CementReferenceValue::query()->firstOrCreate(
                    ['type' => $type, 'name' => $name],
                    ['is_active' => true],
                );
            }
        }
    }

    private function seedLocations(): void
    {
        LokasiPabrik::query()->firstOrCreate(
            ['nama_lokasi' => 'Pabrik Tuban'],
            [
                'kode' => 'TBN',
                'alamat' => 'Tuban',
                'is_active' => true,
            ],
        );
    }

    private function seedIsoStandards(): void
    {
        foreach ($this->isoStandards() as $index => $standard) {
            IsoStandard::query()->updateOrCreate(
                ['code' => $standard['code']],
                [
                    'name' => $standard['name'],
                    'description' => $standard['description'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedSystemCertificates(): void
    {
        $location = $this->locationByName('Pabrik Tuban');

        foreach ($this->systemCertificates() as $payload) {
            $standard = IsoStandard::query()->where('code', $payload['iso'])->firstOrFail();

            $certificate = SertifikatSistemSemen::query()->updateOrCreate(
                ['certificate_number' => $payload['certificate_number']],
                [
                    'lokasi_pabrik_id' => $location->id,
                    'iso_standard_id' => $standard->id,
                    'issuer' => $payload['issuer'],
                    'audit_stage' => $payload['audit_stage'],
                    'scope' => $payload['scope'],
                    'issued_at' => $payload['issued_at'],
                    'berlaku_sd' => $payload['berlaku_sd'],
                    'acquisition_year' => (int) date('Y', strtotime($payload['issued_at'])),
                    'certification_level' => SertifikatSistemSemen::LEVEL_INTERNASIONAL,
                    'certification_category' => $standard->name,
                    'process_owner' => 'Management System',
                    'description' => $standard->description,
                    'notes' => $payload['notes'],
                ],
            );

            app(SystemCertificateAuditTimelineService::class)->syncFor($certificate);
        }
    }

    private function seedCategoriesAndBrands(): array
    {
        $tree = [
            'Semen Hidraulis' => ['PwrPro'],
            'Semen Masonry' => ['Dynamix Masonry'],
            'Semen Portland (OPC)' => ['SprintPro', 'UltraPro'],
            'Semen Portland Kombinasi' => ['Semen Merdeka'],
            'Semen Portland Komposit (PCC)' => ['Dynamix', 'Dynamix Extra Power', 'EzPro', 'Merdeka', 'PwrPro', 'Semen Gresik', 'Semen Merdeka', 'Semen Padang', 'Semen Tonasa'],
            'Semen Portland Pozzolan (PPC)' => ['DuPro - LH', 'DuPro - SBC'],
            'Semen Portland Slag' => ['Max Strength Cement', 'MAXSTRENGTH PRO'],
        ];

        $brands = [];

        foreach ($tree as $categoryName => $brandNames) {
            $category = KategoriSemen::query()->firstOrCreate(['nama_kategori' => $categoryName]);

            foreach ($brandNames as $brandName) {
                $brand = MerekSemen::query()->firstOrCreate([
                    'kategori_semen_id' => $category->id,
                    'nama_merek' => $brandName,
                ]);

                $brands[$categoryName.'|'.$brandName] = $brand;
            }
        }

        return $brands;
    }

    private function isoStandards(): array
    {
        return [
            ['code' => 'ISO 9001', 'name' => 'Sistem Manajemen Mutu', 'description' => 'Standar sistem manajemen mutu untuk proses produksi semen.'],
            ['code' => 'ISO 14001', 'name' => 'Sistem Manajemen Lingkungan', 'description' => 'Standar sistem manajemen lingkungan untuk aktivitas pabrik semen.'],
            ['code' => 'ISO 45001', 'name' => 'Sistem Manajemen K3', 'description' => 'Standar sistem manajemen keselamatan dan kesehatan kerja.'],
            ['code' => 'ISO 50001', 'name' => 'Sistem Manajemen Energi', 'description' => 'Standar sistem manajemen energi untuk efisiensi operasional.'],
            ['code' => 'ISO 37001', 'name' => 'Sistem Manajemen Anti Penyuapan', 'description' => 'Standar sistem manajemen anti penyuapan.'],
            ['code' => 'ISO 27001', 'name' => 'Sistem Manajemen Keamanan Informasi', 'description' => 'Standar sistem manajemen keamanan informasi.'],
        ];
    }

    private function systemCertificates(): array
    {
        return [
            ['iso' => 'ISO 9001', 'certificate_number' => 'ISO-9001-TBN-2024', 'issuer' => 'Lembaga Sertifikasi Sistem', 'audit_stage' => SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_2, 'scope' => 'Produksi semen', 'issued_at' => '2024-08-19', 'berlaku_sd' => '2027-09-11', 'notes' => 'Data awal sertifikat sistem semen.'],
            ['iso' => 'ISO 14001', 'certificate_number' => 'ISO-14001-TBN-2024', 'issuer' => 'Lembaga Sertifikasi Sistem', 'audit_stage' => SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_2, 'scope' => 'Produksi semen', 'issued_at' => '2024-08-19', 'berlaku_sd' => '2027-09-11', 'notes' => 'Data awal sertifikat sistem semen.'],
            ['iso' => 'ISO 45001', 'certificate_number' => 'ISO-45001-TBN-2023', 'issuer' => 'Lembaga Sertifikasi Sistem', 'audit_stage' => SertifikatSistemSemen::AUDIT_STAGE_RENEWAL, 'scope' => 'Produksi semen', 'issued_at' => '2023-05-30', 'berlaku_sd' => '2026-04-27', 'notes' => 'Perlu tindak lanjut renewal.'],
            ['iso' => 'ISO 50001', 'certificate_number' => 'ISO-50001-TBN-2025', 'issuer' => 'Lembaga Sertifikasi Sistem', 'audit_stage' => SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_1, 'scope' => 'Produksi semen', 'issued_at' => '2025-06-20', 'berlaku_sd' => '2028-06-17', 'notes' => 'Data awal sertifikat sistem semen.'],
            ['iso' => 'ISO 37001', 'certificate_number' => 'ISO-37001-TBN-2023', 'issuer' => 'Lembaga Sertifikasi Sistem', 'audit_stage' => SertifikatSistemSemen::AUDIT_STAGE_RENEWAL, 'scope' => 'Produksi semen', 'issued_at' => '2023-10-06', 'berlaku_sd' => '2026-08-09', 'notes' => 'Monitoring renewal.'],
            ['iso' => 'ISO 27001', 'certificate_number' => 'ISO-27001-TBN-2024', 'issuer' => 'Lembaga Sertifikasi Sistem', 'audit_stage' => SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_2, 'scope' => 'Produksi semen', 'issued_at' => '2024-09-25', 'berlaku_sd' => '2027-09-24', 'notes' => 'Data awal sertifikat sistem semen.'],
        ];
    }

    private function sniCertificates(): array
    {
        return [
            ['kategori' => 'Semen Hidraulis', 'merek' => 'PwrPro', 'sni' => 'SNI 8912:2020', 'komoditi' => 'HE', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'B4T', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2030-08-26'],
            ['kategori' => 'Semen Hidraulis', 'merek' => 'PwrPro', 'sni' => 'SNI 7064:2014', 'komoditi' => 'PCC', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'LSPro-B4T Bandung', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2028-01-03'],
            ['kategori' => 'Semen Hidraulis', 'merek' => 'PwrPro', 'sni' => 'SNI 8912:2020', 'komoditi' => 'HE', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'LSPro-B4T Bandung', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2027-01-16'],
            ['kategori' => 'Semen Masonry', 'merek' => 'Dynamix Masonry', 'sni' => 'SNI 3758:2024', 'komoditi' => 'Masonry', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'B4T', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2030-08-26'],
            ['kategori' => 'Semen Masonry', 'merek' => 'Dynamix Masonry', 'sni' => 'SNI 15-3758-2004', 'komoditi' => 'Masonry', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'BSI (Balai Sertifikasi Industri)', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2027-11-06'],
            ['kategori' => 'Semen Portland (OPC)', 'merek' => 'SprintPro', 'sni' => 'SNI 2049-1:2020', 'komoditi' => 'OPC', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'B4T', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2030-08-26'],
            ['kategori' => 'Semen Portland (OPC)', 'merek' => 'UltraPro', 'sni' => 'SNI 2049-1:2020', 'komoditi' => 'OPC', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'B4T', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2030-08-26'],
            ['kategori' => 'Semen Portland (OPC)', 'merek' => 'SprintPro', 'sni' => 'SNI 2049:2015', 'komoditi' => 'OPC', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'LSPro-B4T Bandung', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2028-01-03'],
            ['kategori' => 'Semen Portland (OPC)', 'merek' => 'SprintPro', 'sni' => 'SNI 2049-1:2020', 'komoditi' => 'OPC', 'jenis_sertifikasi' => 'Kesesuaian', 'lspro' => 'LSPro-B4T Bandung', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2028-01-03'],
            ['kategori' => 'Semen Portland (OPC)', 'merek' => 'UltraPro', 'sni' => 'SNI 2049:2015', 'komoditi' => 'OPC', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'LSPro-B4T Bandung', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2028-01-03'],
            ['kategori' => 'Semen Portland Kombinasi', 'merek' => 'Semen Merdeka', 'sni' => 'SNI 7064:2022', 'komoditi' => 'PCC', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'B4T', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2030-08-26'],
            ['kategori' => 'Semen Portland Kombinasi', 'merek' => 'Semen Merdeka', 'sni' => 'SNI 9353:2025', 'komoditi' => 'SPK', 'jenis_sertifikasi' => 'Kesesuaian', 'lspro' => 'LSPro-B4T Bandung', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2029-09-24'],
            ['kategori' => 'Semen Portland Komposit (PCC)', 'merek' => 'Dynamix', 'sni' => 'SNI 7064:2022', 'komoditi' => 'PCC', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'B4T', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2030-08-26'],
            ['kategori' => 'Semen Portland Komposit (PCC)', 'merek' => 'Dynamix Extra Power', 'sni' => 'SNI 7064:2022', 'komoditi' => 'PCC', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'B4T', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2030-08-26'],
            ['kategori' => 'Semen Portland Komposit (PCC)', 'merek' => 'EzPro', 'sni' => 'SNI 7064:2022', 'komoditi' => 'PCC', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'B4T', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2030-08-26'],
            ['kategori' => 'Semen Portland Komposit (PCC)', 'merek' => 'PwrPro', 'sni' => 'SNI 8912:2020', 'komoditi' => 'HE', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'B4T', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2030-08-26'],
            ['kategori' => 'Semen Portland Pozzolan (PPC)', 'merek' => 'DuPro - LH', 'sni' => 'SNI 0302:2014', 'komoditi' => 'PPC', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'B4T', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2030-08-26'],
            ['kategori' => 'Semen Portland Pozzolan (PPC)', 'merek' => 'DuPro - SBC', 'sni' => 'SNI 0302:2014', 'komoditi' => 'PPC', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'B4T', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2030-08-26'],
            ['kategori' => 'Semen Portland Pozzolan (PPC)', 'merek' => 'DuPro - LH', 'sni' => 'SNI 0302:2014', 'komoditi' => 'PPC', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'LSPro-B4T Bandung', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2028-06-23'],
            ['kategori' => 'Semen Portland Pozzolan (PPC)', 'merek' => 'DuPro - SBC', 'sni' => 'SNI 0302:2014', 'komoditi' => 'PPC', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'LSPro-B4T Bandung', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2028-06-23'],
            ['kategori' => 'Semen Portland Slag', 'merek' => 'MAXSTRENGTH PRO', 'sni' => 'SNI 8363:2023', 'komoditi' => 'SPS', 'jenis_sertifikasi' => 'SPPT SNI', 'lspro' => 'B4T', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2030-08-26'],
            ['kategori' => 'Semen Portland Slag', 'merek' => 'MAXSTRENGTH PRO', 'sni' => 'SNI 8363:2017', 'komoditi' => 'SPS', 'jenis_sertifikasi' => 'Kesesuaian', 'lspro' => 'LSPro-B4T Bandung', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2029-02-10'],
        ];
    }

    private function tkdnCertificates(): array
    {
        return [
            ['kategori' => 'Semen Portland (OPC)', 'merek' => 'UltraPro', 'sni' => 'SNI 2049:2015', 'komoditi' => 'OPC', 'persentase_tkdn' => 97.67, 'kemasan' => 'Curah', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2026-12-10'],
            ['kategori' => 'Semen Portland Komposit (PCC)', 'merek' => 'Semen Gresik', 'sni' => 'SNI 7064:2014', 'komoditi' => 'PCC', 'persentase_tkdn' => 84.41, 'kemasan' => 'Kraft 40 Kg', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2026-12-04'],
            ['kategori' => 'Semen Portland Komposit (PCC)', 'merek' => 'Semen Gresik', 'sni' => 'SNI 7064:2014', 'komoditi' => 'PCC', 'persentase_tkdn' => 92.54, 'kemasan' => 'Woven 40 Kg', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2026-12-04'],
            ['kategori' => 'Semen Portland Komposit (PCC)', 'merek' => 'EzPro', 'sni' => 'SNI 7064:2014', 'komoditi' => 'PCC', 'persentase_tkdn' => 97.76, 'kemasan' => 'Curah', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2026-12-10'],
            ['kategori' => 'Semen Portland Komposit (PCC)', 'merek' => 'Semen Gresik', 'sni' => 'SNI 7064:2022', 'komoditi' => 'PCC', 'persentase_tkdn' => 87.49, 'kemasan' => 'Kraft 50 Kg', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2027-10-24'],
            ['kategori' => 'Semen Portland Komposit (PCC)', 'merek' => 'Semen Gresik', 'sni' => 'SNI 7064:2022', 'komoditi' => 'PCC', 'persentase_tkdn' => 95.13, 'kemasan' => 'Woven 50 Kg', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2027-10-24'],
        ];
    }

    private function greenLabelCertificates(): array
    {
        return [
            ['kategori' => 'Semen Hidraulis', 'merek' => 'PwrPro', 'sni' => 'SNI 8912:2020', 'komoditi' => 'HE', 'peringkat' => 'PLATINUM', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2027-01-10'],
            ['kategori' => 'Semen Masonry', 'merek' => 'Dynamix Masonry', 'sni' => 'SNI 3758:2024', 'komoditi' => 'Masonry', 'peringkat' => 'PLATINUM', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2027-01-10'],
            ['kategori' => 'Semen Portland Kombinasi', 'merek' => 'Semen Merdeka', 'sni' => 'SNI 9353:2025', 'komoditi' => 'SPK', 'peringkat' => 'PLATINUM', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2027-01-10'],
            ['kategori' => 'Semen Portland Komposit (PCC)', 'merek' => 'EzPro', 'sni' => 'SNI 7064:2022', 'komoditi' => 'PCC', 'peringkat' => 'PLATINUM', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2026-12-01'],
            ['kategori' => 'Semen Portland Komposit (PCC)', 'merek' => 'PwrPro', 'sni' => 'SNI 8912:2020', 'komoditi' => 'HE', 'peringkat' => 'PLATINUM', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2027-01-10'],
            ['kategori' => 'Semen Portland Komposit (PCC)', 'merek' => 'Semen Gresik', 'sni' => 'SNI 7064:2022', 'komoditi' => 'PCC', 'peringkat' => 'PLATINUM', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2027-01-10'],
            ['kategori' => 'Semen Portland Pozzolan (PPC)', 'merek' => 'DuPro - LH', 'sni' => 'SNI 0302:2014', 'komoditi' => 'PPC', 'peringkat' => 'PLATINUM', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2026-12-01'],
            ['kategori' => 'Semen Portland Pozzolan (PPC)', 'merek' => 'DuPro - SBC', 'sni' => 'SNI 0302:2014', 'komoditi' => 'PPC', 'peringkat' => 'PLATINUM', 'lokasi' => 'Pabrik Tuban', 'berlaku_sd' => '2026-12-01'],
        ];
    }
}
