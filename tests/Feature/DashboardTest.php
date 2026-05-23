<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\CertificateType;
use App\Models\Issuer;
use App\Models\Product;
use App\Models\SertifikatSni;
use App\Models\User;
use Database\Seeders\CementMonitoringSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_are_redirected_to_their_role_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create()->assignAppRole(UserRole::Petugas);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('petugas.dashboard'));
    }

    public function test_petugas_dashboard_displays_role_specific_content(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(CementMonitoringSeeder::class);

        $user = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $response = $this->actingAs($user)->get(route('petugas.dashboard'));

        $response->assertOk()
            ->assertSee('Sertifikat Produk')
            ->assertSee('Daftar Merek')
            ->assertSee('Sertifikat SNI')
            ->assertSee('Sertifikat TKDN')
            ->assertSee('Green Label');
    }

    public function test_admin_cannot_access_petugas_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create()->assignAppRole(UserRole::Admin);

        $response = $this->actingAs($user)->get(route('petugas.dashboard'));

        $response->assertForbidden();
    }

    public function test_dashboard_displays_certificate_status_summary_counts(): void
    {
        Carbon::setTestNow('2026-04-19 08:00:00');
        $this->seed(RolePermissionSeeder::class);
        $this->seed(CementMonitoringSeeder::class);

        $user = User::factory()->create()->assignAppRole(UserRole::Admin);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Total Merek')
            ->assertSee('Sertifikat SNI')
            ->assertSee('Sertifikat TKDN')
            ->assertSee('Green Label')
            ->assertSee((string) SertifikatSni::query()->count());
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
            'name' => 'Dashboard Category',
            'slug' => 'dashboard-category',
            'description' => null,
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'code' => 'PRD-DASH-001',
            'name' => 'Dashboard Product',
            'slug' => 'dashboard-product',
            'description' => null,
            'notes' => null,
            'is_active' => true,
        ]);

        $certificateType = CertificateType::query()->create([
            'name' => 'Dashboard Type',
            'slug' => 'dashboard-type',
            'description' => null,
            'renewal_period_days' => 365,
            'is_active' => true,
        ]);

        $issuer = Issuer::query()->create([
            'name' => 'Dashboard Issuer',
            'code' => 'DSH',
            'contact_person' => 'PIC',
            'email' => 'dashboard-issuer@example.test',
            'phone' => '08111111111',
            'website' => 'https://dashboard-issuer.example.test',
            'address' => 'Bandung',
            'notes' => null,
            'is_active' => true,
        ]);

        return compact('product', 'certificateType', 'issuer');
    }
}
