<?php

namespace Tests\Feature\Certificates;

use App\Enums\CertificateStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Issuer;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_petugas_can_access_certificate_pages(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $certificate = $this->createCertificate();

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $this->actingAs($admin)
            ->get(route('certificates.index'))
            ->assertOk();

        $this->actingAs($petugas)
            ->get(route('certificates.show', $certificate))
            ->assertOk();
    }

    public function test_authorized_user_can_create_certificate_with_document(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        ['product' => $product, 'certificateType' => $certificateType, 'issuer' => $issuer] = $this->createDependencies();
        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $response = $this->actingAs($petugas)->post(route('certificates.store'), [
            'product_id' => $product->id,
            'certificate_type_id' => $certificateType->id,
            'issuer_id' => $issuer->id,
            'certificate_number' => 'CERT-2026-0009',
            'issue_date' => '2026-04-01',
            'expiry_date' => '2027-04-01',
            'document' => UploadedFile::fake()->create('sertifikat.pdf', 150, 'application/pdf'),
            'notes' => 'Dokumen hasil unggah petugas.',
        ]);

        $certificate = Certificate::query()->firstOrFail();

        $response->assertRedirect(route('certificates.show', $certificate))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
            'product_id' => $product->id,
            'certificate_type_id' => $certificateType->id,
            'issuer_id' => $issuer->id,
            'certificate_number' => 'CERT-2026-0009',
            'issued_by_user_id' => $petugas->id,
            'updated_by_user_id' => $petugas->id,
            'status' => CertificateStatus::Active->value,
        ]);

        $this->assertNotNull($certificate->file_path);
        Storage::disk('local')->assertExists($certificate->file_path);
        Storage::disk('public')->assertMissing($certificate->file_path);
    }

    public function test_certificate_document_can_be_downloaded(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $certificate = $this->createCertificate();
        Storage::disk('local')->put($certificate->file_path, 'dummy-file');

        $response = $this->actingAs($admin)->get(route('certificates.download', $certificate));

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }

    public function test_updating_certificate_replaces_document_and_deleting_removes_it(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $certificate = $this->createCertificate();

        Storage::disk('local')->put($certificate->file_path, 'old-file');

        $response = $this->actingAs($admin)->put(route('certificates.update', $certificate), [
            'product_id' => $certificate->product_id,
            'certificate_type_id' => $certificate->certificate_type_id,
            'issuer_id' => $certificate->issuer_id,
            'certificate_number' => 'CERT-2026-UPDATED',
            'issue_date' => '2026-01-05',
            'expiry_date' => now()->addDays(10)->format('Y-m-d'),
            'document' => UploadedFile::fake()->image('baru.jpg'),
            'notes' => 'Dokumen diperbarui.',
        ]);

        $certificate->refresh();

        $response->assertRedirect(route('certificates.show', $certificate))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
            'certificate_number' => 'CERT-2026-UPDATED',
            'updated_by_user_id' => $admin->id,
            'status' => CertificateStatus::ExpiringSoon->value,
        ]);

        Storage::disk('local')->assertExists($certificate->file_path);
        Storage::disk('local')->assertMissing('certificates/cert-lama.pdf');
        Storage::disk('public')->assertMissing($certificate->file_path);

        $deleteResponse = $this->actingAs($admin)->delete(route('certificates.destroy', $certificate));

        $deleteResponse->assertRedirect(route('certificates.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('certificates', [
            'id' => $certificate->id,
        ]);
        Storage::disk('local')->assertMissing($certificate->file_path);
    }

    private function createCertificate(): Certificate
    {
        ['product' => $product, 'certificateType' => $certificateType, 'issuer' => $issuer] = $this->createDependencies();
        $user = User::factory()->create()->assignAppRole(UserRole::Admin);

        return Certificate::query()->create([
            'product_id' => $product->id,
            'certificate_type_id' => $certificateType->id,
            'issuer_id' => $issuer->id,
            'issued_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id,
            'certificate_number' => 'CERT-2026-0001',
            'issued_at' => '2026-01-01',
            'expires_at' => '2026-12-31',
            'file_path' => 'certificates/cert-lama.pdf',
            'status' => CertificateStatus::Active,
            'notes' => 'Catatan awal',
        ]);
    }

    /**
     * @return array{product: Product, certificateType: CertificateType, issuer: Issuer}
     */
    private function createDependencies(): array
    {
        $category = Category::query()->create([
            'name' => 'Semen Portland Komposit (PCC)',
            'slug' => 'semen-portland-komposit-pcc',
            'description' => null,
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'code' => 'PRD-001',
            'name' => 'Produk Uji',
            'slug' => 'produk-uji',
            'description' => 'Produk untuk pengujian',
            'notes' => null,
            'is_active' => true,
        ]);

        $certificateType = CertificateType::query()->create([
            'name' => 'Sertifikat Uji',
            'slug' => 'sertifikat-uji',
            'description' => 'Jenis sertifikat untuk test',
            'renewal_period_days' => 365,
            'is_active' => true,
        ]);

        $issuer = Issuer::query()->create([
            'name' => 'Lembaga Uji',
            'code' => 'LMB-UJI',
            'contact_person' => 'Petugas Lembaga',
            'email' => 'issuer@example.test',
            'phone' => '08123456789',
            'website' => 'https://issuer.example.test',
            'address' => 'Bandung',
            'notes' => null,
            'is_active' => true,
        ]);

        return compact('product', 'certificateType', 'issuer');
    }
}
