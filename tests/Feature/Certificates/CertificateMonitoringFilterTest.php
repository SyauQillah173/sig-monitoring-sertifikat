<?php

namespace Tests\Feature\Certificates;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Issuer;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CertificateMonitoringFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_index_can_be_filtered_by_monitoring_status(): void
    {
        Carbon::setTestNow('2026-04-19 09:00:00');
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create()->assignAppRole(UserRole::Petugas);
        $dependencies = $this->createCertificateDependencies();

        Certificate::query()->create([
            'product_id' => $dependencies['product']->id,
            'certificate_type_id' => $dependencies['certificateType']->id,
            'issuer_id' => $dependencies['issuer']->id,
            'issued_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id,
            'certificate_number' => 'CERT-FLT-001',
            'issued_at' => '2026-01-01',
            'expires_at' => '2026-06-01',
            'status' => 'active',
        ]);

        Certificate::query()->create([
            'product_id' => $dependencies['product']->id,
            'certificate_type_id' => $dependencies['certificateType']->id,
            'issuer_id' => $dependencies['issuer']->id,
            'issued_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id,
            'certificate_number' => 'CERT-FLT-002',
            'issued_at' => '2026-01-01',
            'expires_at' => '2026-04-25',
            'status' => 'active',
        ]);

        Certificate::query()->create([
            'product_id' => $dependencies['product']->id,
            'certificate_type_id' => $dependencies['certificateType']->id,
            'issuer_id' => $dependencies['issuer']->id,
            'issued_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id,
            'certificate_number' => 'CERT-FLT-003',
            'issued_at' => '2026-01-01',
            'expires_at' => '2026-04-10',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('certificates.index', [
            'status' => 'expiring_soon',
        ]));

        $response->assertOk()
            ->assertSee('CERT-FLT-002')
            ->assertDontSee('CERT-FLT-001')
            ->assertDontSee('CERT-FLT-003')
            ->assertSee('Akan Habis');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @return array{product: Product, certificateType: CertificateType, issuer: Issuer}
     */
    private function createCertificateDependencies(): array
    {
        $category = Category::query()->create([
            'name' => 'Filter Category',
            'slug' => 'filter-category',
            'description' => null,
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'code' => 'PRD-FILTER-001',
            'name' => 'Filter Product',
            'slug' => 'filter-product',
            'description' => null,
            'notes' => null,
            'is_active' => true,
        ]);

        $certificateType = CertificateType::query()->create([
            'name' => 'Filter Type',
            'slug' => 'filter-type',
            'description' => null,
            'renewal_period_days' => 365,
            'is_active' => true,
        ]);

        $issuer = Issuer::query()->create([
            'name' => 'Filter Issuer',
            'code' => 'FLT',
            'contact_person' => 'PIC',
            'email' => 'filter-issuer@example.test',
            'phone' => '08111111112',
            'website' => 'https://filter-issuer.example.test',
            'address' => 'Jakarta',
            'notes' => null,
            'is_active' => true,
        ]);

        return compact('product', 'certificateType', 'issuer');
    }
}
