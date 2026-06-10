<?php

namespace Tests\Feature\Cement;

use App\Enums\UserRole;
use App\Models\SertifikatGreenLabel;
use App\Models\SertifikatSni;
use App\Models\SertifikatTkdn;
use App\Models\User;
use Database\Seeders\CementMonitoringSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CementImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_preview_and_store_cement_certificate_excel_import(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(CementMonitoringSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $file = $this->makeImportWorkbook();

        try {
            $this->actingAs($admin)
                ->post(route('cement.import.preview'), ['file_excel' => $file])
                ->assertRedirect(route('cement.import.index'))
                ->assertSessionHas('success')
                ->assertSessionHas('cement_certificate_import_preview', fn ($value) => is_string($value));

            $this->actingAs($admin)
                ->get(route('cement.import.index'))
                ->assertOk()
                ->assertSee('Preview Import')
                ->assertSee('Import siap disimpan.');

            $this->actingAs($admin)
                ->post(route('cement.import.store'))
                ->assertRedirect(route('cement.products.index'))
                ->assertSessionHas('success');

            $this->assertNotNull(SertifikatSni::query()->where('sni', 'SNI 7064:2022')->whereDate('berlaku_sd', '2030-01-01')->first());
            $this->assertNotNull(SertifikatTkdn::query()->where('persentase_tkdn', 42.5)->whereDate('berlaku_sd', '2030-02-01')->first());
            $this->assertNotNull(SertifikatGreenLabel::query()->where('peringkat', 'PLATINUM')->whereDate('berlaku_sd', '2030-03-01')->first());
            $this->assertNull(SertifikatSni::query()->where('sni', 'SNI 7064:2022')->whereDate('berlaku_sd', '2030-01-01')->firstOrFail()->file_sertifikat);
        } finally {
            @unlink($file->getPathname());
        }
    }

    public function test_import_preview_rejects_certificate_filename_that_is_not_uploaded_to_storage(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(CementMonitoringSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $file = $this->makeImportWorkbook('sertifikat-diketik-manual.pdf');

        try {
            $this->actingAs($admin)
                ->post(route('cement.import.preview'), ['file_excel' => $file])
                ->assertRedirect(route('cement.import.index'))
                ->assertSessionHas('error');

            $this->actingAs($admin)
                ->get(route('cement.import.index'))
                ->assertOk()
                ->assertSee('file_sertifikat opsional');
        } finally {
            @unlink($file->getPathname());
        }
    }

    public function test_company_excel_header_variants_and_indonesian_dates_can_be_imported(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(CementMonitoringSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $file = $this->makeCompanyWorkbook();

        try {
            $this->actingAs($admin)
                ->post(route('cement.import.preview'), ['file_excel' => $file])
                ->assertRedirect(route('cement.import.index'))
                ->assertSessionHas('success');

            $this->actingAs($admin)
                ->post(route('cement.import.store'))
                ->assertRedirect(route('cement.products.index'));

            $this->assertSame(
                '2029-06-16',
                SertifikatSni::query()->where('sni', 'SNI 8912:2020')->where('komoditi', 'HE')->where('lspro', 'B4T')->latest('id')->firstOrFail()->berlaku_sd->format('Y-m-d'),
            );
            $this->assertSame(
                '2029-06-17',
                SertifikatTkdn::query()->where('sni', 'SNI 2049:2015')->where('komoditi', 'OPC')->where('kemasan', 'Curah')->latest('id')->firstOrFail()->berlaku_sd->format('Y-m-d'),
            );
            $this->assertSame(
                '2029-06-18',
                SertifikatGreenLabel::query()->where('sni', 'SNI 8912:2020')->where('komoditi', 'HE')->where('peringkat', 'PLATINUM')->latest('id')->firstOrFail()->berlaku_sd->format('Y-m-d'),
            );
        } finally {
            @unlink($file->getPathname());
        }
    }

    public function test_import_preview_rejects_free_text_typo_that_is_not_in_master_database(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(CementMonitoringSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $file = $this->makeImportWorkbook(brandName: 'PwrPru');

        try {
            $this->actingAs($admin)
                ->post(route('cement.import.preview'), ['file_excel' => $file])
                ->assertRedirect(route('cement.import.index'))
                ->assertSessionHas('error');

            $this->actingAs($admin)
                ->get(route('cement.import.index'))
                ->assertOk()
                ->assertSee('kategori/merek tidak cocok dengan master database');
        } finally {
            @unlink($file->getPathname());
        }
    }

    public function test_import_only_stores_new_rows_and_skips_existing_certificate_data(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(CementMonitoringSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $firstFile = $this->makeImportWorkbook();
        $secondFile = $this->makeImportWorkbook();

        try {
            $this->actingAs($admin)
                ->post(route('cement.import.preview'), ['file_excel' => $firstFile])
                ->assertRedirect(route('cement.import.index'))
                ->assertSessionHas('success');

            $this->actingAs($admin)
                ->post(route('cement.import.store'))
                ->assertRedirect(route('cement.products.index'))
                ->assertSessionHas('success');

            $this->actingAs($admin)
                ->post(route('cement.import.preview'), ['file_excel' => $secondFile])
                ->assertRedirect(route('cement.import.index'))
                ->assertSessionHas('success');

            $this->actingAs($admin)
                ->get(route('cement.import.index'))
                ->assertOk()
                ->assertSee('3 data dilewati');

            $this->actingAs($admin)
                ->post(route('cement.import.store'))
                ->assertRedirect(route('cement.products.index'))
                ->assertSessionHas('success', 'Tidak ada data baru untuk disimpan. Semua data import sudah ada di sistem.');

            $this->assertSame(1, SertifikatSni::query()->where('sni', 'SNI 7064:2022')->whereDate('berlaku_sd', '2030-01-01')->count());
            $this->assertSame(1, SertifikatTkdn::query()->where('persentase_tkdn', 42.5)->whereDate('berlaku_sd', '2030-02-01')->count());
            $this->assertSame(1, SertifikatGreenLabel::query()->where('peringkat', 'PLATINUM')->whereDate('berlaku_sd', '2030-03-01')->count());
        } finally {
            @unlink($firstFile->getPathname());
            @unlink($secondFile->getPathname());
        }
    }

    public function test_import_allows_different_valid_master_combination_but_still_rejects_typos(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(CementMonitoringSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $file = $this->makeDifferentValidCombinationWorkbook();

        try {
            $this->actingAs($admin)
                ->post(route('cement.import.preview'), ['file_excel' => $file])
                ->assertRedirect(route('cement.import.index'))
                ->assertSessionHas('success');

            $this->actingAs($admin)
                ->post(route('cement.import.store'))
                ->assertRedirect(route('cement.products.index'))
                ->assertSessionHas('success');

            $this->assertNotNull(SertifikatSni::query()
                ->where('sni', 'SNI 3758:2024')
                ->where('komoditi', 'Masonry')
                ->where('lspro', 'B4T')
                ->whereDate('berlaku_sd', '2031-01-01')
                ->first());
        } finally {
            @unlink($file->getPathname());
        }

        $typoFile = $this->makeImportWorkbook(brandName: 'Dynamix Masonri');

        try {
            $this->actingAs($admin)
                ->post(route('cement.import.preview'), ['file_excel' => $typoFile])
                ->assertRedirect(route('cement.import.index'))
                ->assertSessionHas('error');
        } finally {
            @unlink($typoFile->getPathname());
        }
    }

    public function test_admin_can_download_template_with_master_database_dropdowns(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(CementMonitoringSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);

        $this->actingAs($admin)
            ->get(route('cement.exports.template'))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_petugas_cannot_access_cement_excel_import(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $this->actingAs($petugas)
            ->get(route('cement.import.index'))
            ->assertForbidden();
    }

    private function makeImportWorkbook(string $certificatePath = '', string $brandName = 'PwrPro'): UploadedFile
    {
        $spreadsheet = new Spreadsheet;

        $sniSheet = $spreadsheet->getActiveSheet();
        $sniSheet->setTitle('Sertifikat SNI');
        $sniSheet->fromArray([
            ['kategori', 'merek', 'sni', 'komoditi', 'jenis_sertifikasi', 'lspro', 'lokasi', 'berlaku_sd', 'file_sertifikat'],
            ['Semen Portland Komposit (PCC)', $brandName, 'SNI 7064:2022', 'PCC', 'SPPT SNI', 'B4T', 'Pabrik Tuban', '2030-01-01', $certificatePath],
        ]);

        $tkdnSheet = $spreadsheet->createSheet();
        $tkdnSheet->setTitle('Sertifikat TKDN');
        $tkdnSheet->fromArray([
            ['kategori', 'merek', 'sni', 'komoditi', 'persentase_tkdn', 'kemasan', 'lokasi', 'berlaku_sd', 'file_sertifikat'],
            ['Semen Portland Komposit (PCC)', $brandName, 'SNI 7064:2022', 'PCC', '42,5', 'Curah', 'Pabrik Tuban', '2030-02-01', ''],
        ]);

        $greenLabelSheet = $spreadsheet->createSheet();
        $greenLabelSheet->setTitle('Sertifikat Green Label');
        $greenLabelSheet->fromArray([
            ['kategori', 'merek', 'sni', 'komoditi', 'peringkat', 'lokasi', 'berlaku_sd', 'file_sertifikat'],
            ['Semen Portland Komposit (PCC)', $brandName, 'SNI 8912:2020', 'HE', 'PLATINUM', 'Pabrik Tuban', '2030-03-01', ''],
        ]);

        $directory = storage_path('framework/testing');

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $path = $directory.DIRECTORY_SEPARATOR.'cement-import-'.bin2hex(random_bytes(6)).'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile(
            $path,
            'cement-import.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    private function makeCompanyWorkbook(): UploadedFile
    {
        $spreadsheet = new Spreadsheet;

        $sniSheet = $spreadsheet->getActiveSheet();
        $sniSheet->setTitle('Sertifikat SNI');
        $sniSheet->fromArray([
            ['Kategori', 'Merek', 'SNI', 'Komoditi', 'Jenis Sertifikasi', 'LSPro', 'Lokasi', 'Berlaku SD', 'File Sertifikat'],
            ['semen hidraulis', 'pwrpro', 'sni 8912:2020', 'he', 'sppt sni', 'b4t', 'pabrik tuban', '16 juni 2029', ''],
        ]);

        $tkdnSheet = $spreadsheet->createSheet();
        $tkdnSheet->setTitle('Sertifikat TKDN');
        $tkdnSheet->fromArray([
            ['Kategori', 'Merek', 'SNI', 'Komoditi', 'Persentase TKDN', 'Kemasan', 'Lokasi', 'Berlaku SD', 'File Sertifikat'],
            ['semen portland (opc)', 'ultrapro', 'sni 2049:2015', 'opc', '44,2', 'curah', 'pabrik tuban', '17 Juni 2029', ''],
        ]);

        $greenLabelSheet = $spreadsheet->createSheet();
        $greenLabelSheet->setTitle('Sertifikat Green Label');
        $greenLabelSheet->fromArray([
            ['Kategori', 'Merek', 'SNI', 'Komoditi', 'Peringkat', 'Lokasi', 'Berlaku SD', 'File Sertifikat'],
            ['semen hidraulis', 'pwrpro', 'sni 8912:2020', 'he', 'platinum', 'pabrik tuban', '18 juni 2029', ''],
        ]);

        $directory = storage_path('framework/testing');

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $path = $directory.DIRECTORY_SEPARATOR.'company-cement-import-'.bin2hex(random_bytes(6)).'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile(
            $path,
            'company-cement-import.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    private function makeDifferentValidCombinationWorkbook(): UploadedFile
    {
        $spreadsheet = new Spreadsheet;

        $sniSheet = $spreadsheet->getActiveSheet();
        $sniSheet->setTitle('Sertifikat SNI');
        $sniSheet->fromArray([
            ['kategori', 'merek', 'sni', 'komoditi', 'jenis_sertifikasi', 'lspro', 'lokasi', 'berlaku_sd', 'file_sertifikat'],
            ['Semen Masonry', 'Dynamix Masonry', 'SNI 3758:2024', 'Masonry', 'SPPT SNI', 'B4T', 'Pabrik Tuban', '2031-01-01', ''],
        ]);

        $tkdnSheet = $spreadsheet->createSheet();
        $tkdnSheet->setTitle('Sertifikat TKDN');
        $tkdnSheet->fromArray([
            ['kategori', 'merek', 'sni', 'komoditi', 'persentase_tkdn', 'kemasan', 'lokasi', 'berlaku_sd', 'file_sertifikat'],
        ]);

        $greenLabelSheet = $spreadsheet->createSheet();
        $greenLabelSheet->setTitle('Sertifikat Green Label');
        $greenLabelSheet->fromArray([
            ['kategori', 'merek', 'sni', 'komoditi', 'peringkat', 'lokasi', 'berlaku_sd', 'file_sertifikat'],
        ]);

        $directory = storage_path('framework/testing');

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $path = $directory.DIRECTORY_SEPARATOR.'different-valid-cement-import-'.bin2hex(random_bytes(6)).'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile(
            $path,
            'different-valid-cement-import.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }
}
