<?php

namespace Tests\Feature;

use App\Models\IsoStandard;
use App\Models\KategoriSemen;
use App\Models\LokasiPabrik;
use App\Models\MerekSemen;
use App\Models\SertifikatGreenLabel;
use App\Models\SertifikatSistemSemen;
use App\Models\SertifikatSni;
use App\Models\SertifikatTkdn;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_redirects_authenticated_users_to_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_landing_page_displays_dynamic_cement_monitoring_summary_from_database(): void
    {
        Carbon::setTestNow('2026-05-19 08:00:00');

        $category = KategoriSemen::query()->create(['nama_kategori' => 'Semen Portland Komposit (PCC)']);
        $brandA = MerekSemen::query()->create(['kategori_semen_id' => $category->id, 'nama_merek' => 'Dynamix']);
        $brandB = MerekSemen::query()->create(['kategori_semen_id' => $category->id, 'nama_merek' => 'Semen Gresik']);

        SertifikatSni::query()->create([
            'merek_semen_id' => $brandA->id,
            'sni' => 'SNI 7064:2022',
            'komoditi' => 'PCC',
            'jenis_sertifikasi' => 'SPPT SNI',
            'lspro' => 'B4T',
            'lokasi' => 'Pabrik Tuban',
            'berlaku_sd' => '2030-08-26',
            'file_sertifikat' => 'uploads/sertifikat/sni.pdf',
        ]);

        SertifikatTkdn::query()->create([
            'merek_semen_id' => $brandB->id,
            'sni' => 'SNI 7064:2022',
            'komoditi' => 'PCC',
            'persentase_tkdn' => 87.49,
            'kemasan' => 'Kraft 50 Kg',
            'lokasi' => 'Pabrik Tuban',
            'berlaku_sd' => '2026-06-01',
        ]);

        SertifikatGreenLabel::query()->create([
            'merek_semen_id' => $brandB->id,
            'sni' => 'SNI 7064:2022',
            'komoditi' => 'PCC',
            'peringkat' => 'PLATINUM',
            'lokasi' => 'Pabrik Tuban',
            'berlaku_sd' => '2026-05-01',
        ]);

        $location = LokasiPabrik::query()->create(['nama_lokasi' => 'Pabrik Tuban', 'is_active' => true]);
        $standard = IsoStandard::query()->create([
            'code' => 'ISO 9001',
            'name' => 'Sistem Manajemen Mutu',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        SertifikatSistemSemen::query()->create([
            'lokasi_pabrik_id' => $location->id,
            'iso_standard_id' => $standard->id,
            'certificate_number' => 'ISO-9001-LANDING',
            'issuer' => 'Bureau Veritas',
            'audit_stage' => SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_1,
            'scope' => 'Produksi semen',
            'issued_at' => '2026-01-01',
            'berlaku_sd' => '2028-01-01',
            'acquisition_year' => 2026,
            'certification_level' => SertifikatSistemSemen::LEVEL_INTERNASIONAL,
            'certification_category' => 'Sistem Manajemen Mutu',
            'notes' => 'Catatan internal tidak boleh tampil publik.',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Monitoring sertifikat produk dan sistem ISO semen yang terpusat.')
            ->assertSee('Total Merek')
            ->assertSee('Sertifikat SNI')
            ->assertSee('Sertifikat TKDN')
            ->assertSee('Green Label')
            ->assertSee('Sistem ISO')
            ->assertSee('ISO 9001')
            ->assertSee('Sertifikasi Sistem ISO')
            ->assertSee('Bureau Veritas')
            ->assertSee('Internasional')
            ->assertSee('2026')
            ->assertSee('Akan Berakhir')
            ->assertSee('Kadaluarsa')
            ->assertSee('Dynamix')
            ->assertSee('Semen Gresik')
            ->assertSee('SNI 7064:2022')
            ->assertDontSee('Catatan internal tidak boleh tampil publik.')
            ->assertDontSee('ISO-9001-LANDING')
            ->assertDontSee('BPOM')
            ->assertDontSee('Halal');

        Carbon::setTestNow();
    }

    public function test_landing_summary_endpoint_returns_fresh_public_partial(): void
    {
        $response = $this->get(route('home.summary'));

        $response->assertOk()
            ->assertHeader('cache-control')
            ->assertSee('Monitoring sertifikat.');
    }

    public function test_landing_sections_can_be_hidden_from_cms_settings(): void
    {
        foreach ([
            'show_landing_certificate_mix',
            'show_landing_priority_feed',
            'show_landing_public_iso',
        ] as $key) {
            SystemSetting::query()->create([
                'key' => $key,
                'value' => '0',
                'group' => 'public_landing',
                'label' => $key,
            ]);
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Distribusi Jenis Sertifikat Semen')
            ->assertDontSee('Prioritas Operasional')
            ->assertDontSee('Sertifikasi Sistem ISO');
    }

    public function test_public_iso_privacy_fields_can_be_hidden_from_cms_settings(): void
    {
        $location = LokasiPabrik::query()->create(['nama_lokasi' => 'Pabrik Rahasia', 'is_active' => true]);
        $standard = IsoStandard::query()->create([
            'code' => 'ISO 27001',
            'name' => 'Sistem Manajemen Keamanan Informasi',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        SertifikatSistemSemen::query()->create([
            'lokasi_pabrik_id' => $location->id,
            'iso_standard_id' => $standard->id,
            'certificate_number' => 'ISO-27001-PRIVATE-NUMBER',
            'issuer' => 'Lembaga Rahasia',
            'audit_stage' => SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_1,
            'scope' => 'Scope operasional rahasia',
            'issued_at' => '2026-01-01',
            'berlaku_sd' => '2028-01-01',
            'acquisition_year' => 2026,
            'certification_level' => SertifikatSistemSemen::LEVEL_INTERNASIONAL,
            'certification_category' => 'Kategori Rahasia',
            'notes' => 'Catatan internal rahasia.',
        ]);

        foreach ([
            'show_public_iso_location',
            'show_public_iso_issuer',
            'show_public_iso_scope',
            'show_public_iso_validity',
            'show_public_iso_status',
            'show_public_iso_level_year',
            'show_public_iso_category',
        ] as $key) {
            SystemSetting::query()->create([
                'key' => $key,
                'value' => '0',
                'group' => 'public_landing',
                'label' => $key,
            ]);
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('ISO 27001')
            ->assertSee('Sistem Manajemen Keamanan Informasi')
            ->assertDontSee('Pabrik Rahasia')
            ->assertDontSee('Lembaga Rahasia')
            ->assertDontSee('Scope operasional rahasia')
            ->assertDontSee('01 Jan 2028')
            ->assertDontSee('Internasional')
            ->assertDontSee('Kategori Rahasia')
            ->assertDontSee('ISO-27001-PRIVATE-NUMBER')
            ->assertDontSee('Catatan internal rahasia.');
    }
}
