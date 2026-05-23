<?php

namespace Tests\Feature\Reports;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Issuer;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class CertificateMonitoringReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_access_reports(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $this->actingAs($petugas)
            ->get(route('reports.certificates.index'))
            ->assertForbidden();
    }

    public function test_admin_can_filter_certificate_report(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        ['category' => $category, 'product' => $product, 'certificateType' => $certificateType, 'issuer' => $issuer] = $this->createDependencies();

        Certificate::query()->create([
            'product_id' => $product->id,
            'certificate_type_id' => $certificateType->id,
            'issuer_id' => $issuer->id,
            'issued_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
            'certificate_number' => 'CERT-RPT-001',
            'issued_at' => '2026-01-01',
            'expires_at' => now()->addDays(10)->toDateString(),
            'status' => 'active',
        ]);

        Certificate::query()->create([
            'product_id' => $product->id,
            'certificate_type_id' => $certificateType->id,
            'issuer_id' => $issuer->id,
            'issued_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
            'certificate_number' => 'CERT-RPT-002',
            'issued_at' => '2026-01-01',
            'expires_at' => now()->addDays(45)->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('reports.certificates.index', [
            'category_id' => $category->id,
            'product_id' => $product->id,
            'status' => 'expiring_soon',
        ]));

        $response->assertOk()
            ->assertSee('CERT-RPT-001')
            ->assertDontSee('CERT-RPT-002')
            ->assertSee('Laporan Monitoring Sertifikat');
    }

    public function test_admin_can_export_report_to_pdf(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        ['product' => $product, 'certificateType' => $certificateType, 'issuer' => $issuer] = $this->createDependencies();

        Certificate::query()->create([
            'product_id' => $product->id,
            'certificate_type_id' => $certificateType->id,
            'issuer_id' => $issuer->id,
            'issued_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
            'certificate_number' => 'CERT-PDF-001',
            'issued_at' => '2026-01-01',
            'expires_at' => now()->addDays(20)->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('reports.certificates.export-pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_can_export_report_to_excel(): void
    {
        Excel::fake();
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        ['product' => $product, 'certificateType' => $certificateType, 'issuer' => $issuer] = $this->createDependencies();

        Certificate::query()->create([
            'product_id' => $product->id,
            'certificate_type_id' => $certificateType->id,
            'issuer_id' => $issuer->id,
            'issued_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
            'certificate_number' => 'CERT-XLS-001',
            'issued_at' => '2026-01-01',
            'expires_at' => now()->addDays(20)->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('reports.certificates.export-excel'));

        $response->assertOk();

        Excel::assertDownloaded('laporan-monitoring-sertifikat.xlsx');
    }

    /**
     * @return array{category: Category, product: Product, certificateType: CertificateType, issuer: Issuer}
     */
    private function createDependencies(): array
    {
        $category = Category::query()->create([
            'name' => 'Laporan Kategori',
            'slug' => 'laporan-kategori',
            'description' => null,
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'code' => 'PRD-RPT-001',
            'name' => 'Produk Laporan',
            'slug' => 'produk-laporan',
            'description' => null,
            'notes' => null,
            'is_active' => true,
        ]);

        $certificateType = CertificateType::query()->create([
            'name' => 'Jenis Laporan',
            'slug' => 'jenis-laporan',
            'description' => null,
            'renewal_period_days' => 365,
            'is_active' => true,
        ]);

        $issuer = Issuer::query()->create([
            'name' => 'Issuer Laporan',
            'code' => 'LPR',
            'contact_person' => 'PIC',
            'email' => 'issuer-laporan@example.test',
            'phone' => '08123456000',
            'website' => 'https://issuer-laporan.example.test',
            'address' => 'Jakarta',
            'notes' => null,
            'is_active' => true,
        ]);

        return compact('category', 'product', 'certificateType', 'issuer');
    }
}
