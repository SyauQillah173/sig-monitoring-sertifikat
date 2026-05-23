<?php

namespace Tests\Feature\Database;

use App\Enums\CertificateRenewalStatus;
use App\Enums\CertificateStatus;
use App\Enums\NotificationStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\CertificateRenewal;
use App\Models\CertificateType;
use App\Models\Issuer;
use App\Models\Notification;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CertificateDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_seeder_populates_base_reference_tables(): void
    {
        $this->seed(MasterDataSeeder::class);

        $this->assertDatabaseCount('categories', 7);
        $this->assertDatabaseCount('products', 14);
        $this->assertDatabaseCount('certificate_types', 3);
        $this->assertDatabaseCount('issuers', 3);

        $product = Product::query()->where('name', 'Dynamix')->firstOrFail();

        $this->assertSame('Semen Portland Komposit (PCC)', $product->category->name);
    }

    public function test_certificate_domain_models_are_connected_with_expected_relations(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $category = Category::query()->create([
            'name' => 'Suplemen',
            'slug' => 'suplemen',
            'description' => 'Produk suplemen kesehatan.',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'code' => 'PRD-SPL-001',
            'name' => 'Vitamin Harian',
            'slug' => 'vitamin-harian',
            'description' => 'Produk vitamin suplemen harian.',
            'notes' => null,
            'is_active' => true,
        ]);

        $certificateType = CertificateType::query()->create([
            'name' => 'Sertifikat Uji Mutu',
            'slug' => 'sertifikat-uji-mutu',
            'description' => 'Sertifikat hasil uji mutu produk.',
            'renewal_period_days' => 365,
            'is_active' => true,
        ]);

        $issuer = Issuer::query()->create([
            'name' => 'Lembaga Uji Nasional',
            'code' => 'LUN',
            'contact_person' => 'Admin LUN',
            'email' => 'layanan@lun.test',
            'phone' => '021000000',
            'website' => 'https://lun.test',
            'address' => 'Jakarta',
            'notes' => null,
            'is_active' => true,
        ]);

        $certificate = Certificate::query()->create([
            'product_id' => $product->id,
            'certificate_type_id' => $certificateType->id,
            'issuer_id' => $issuer->id,
            'issued_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id,
            'certificate_number' => 'CERT-2026-0001',
            'title' => 'Sertifikat Uji Mutu Vitamin Harian',
            'issued_at' => '2026-01-10',
            'expires_at' => '2027-01-10',
            'file_path' => 'certificates/cert-2026-0001.pdf',
            'status' => CertificateStatus::Active,
            'notes' => 'Dokumen awal penerbitan.',
        ]);

        $renewal = CertificateRenewal::query()->create([
            'certificate_id' => $certificate->id,
            'renewed_by_user_id' => $user->id,
            'renewal_number' => 1,
            'previous_certificate_number' => 'CERT-2026-0001',
            'new_certificate_number' => 'CERT-2027-0001',
            'renewal_date' => '2026-12-15',
            'previous_expires_at' => '2027-01-10',
            'new_expires_at' => '2028-01-10',
            'file_path' => 'renewals/cert-2027-0001.pdf',
            'status' => CertificateRenewalStatus::Completed,
            'notes' => 'Perpanjangan pertama selesai.',
        ]);

        $notification = Notification::query()->create([
            'user_id' => $user->id,
            'certificate_id' => $certificate->id,
            'title' => 'Sertifikat akan habis masa berlaku',
            'message' => 'Segera lakukan tindak lanjut perpanjangan.',
            'notification_type' => 'expiry_reminder',
            'status' => NotificationStatus::Unread,
            'data' => ['days_remaining' => 30],
        ]);

        $auditLog = AuditLog::query()->create([
            'user_id' => $user->id,
            'auditable_type' => Certificate::class,
            'auditable_id' => $certificate->id,
            'action' => 'created',
            'description' => 'Certificate created',
            'old_values' => null,
            'new_values' => ['status' => CertificateStatus::Active->value],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $this->assertTrue($product->certificates->contains($certificate));
        $this->assertTrue($certificate->renewals->contains($renewal));
        $this->assertTrue($certificate->notifications->contains($notification));
        $this->assertTrue($certificate->auditLogs->contains($auditLog));
        $this->assertSame($issuer->id, $certificate->issuer->id);
        $this->assertSame($certificateType->id, $certificate->certificateType->id);
        $this->assertSame($user->id, $certificate->issuedBy->id);
        $this->assertSame($user->id, $renewal->renewedBy->id);
        $this->assertSame($user->id, $notification->user->id);
        $this->assertSame($certificate->id, $auditLog->auditable->id);
    }

    public function test_certificate_monitoring_status_is_derived_from_expiry_date_and_scopes(): void
    {
        Carbon::setTestNow('2026-04-19 10:00:00');

        $active = Certificate::query()->create([
            'product_id' => $this->createProduct()->id,
            'certificate_type_id' => $this->createCertificateType()->id,
            'issuer_id' => $this->createIssuer()->id,
            'certificate_number' => 'CERT-ACTIVE-001',
            'issued_at' => '2026-01-01',
            'expires_at' => '2026-06-01',
            'status' => CertificateStatus::Active,
        ]);

        $expiringSoon = Certificate::query()->create([
            'product_id' => $this->createProduct()->id,
            'certificate_type_id' => $this->createCertificateType()->id,
            'issuer_id' => $this->createIssuer()->id,
            'certificate_number' => 'CERT-EXPIRING-001',
            'issued_at' => '2026-01-01',
            'expires_at' => '2026-05-10',
            'status' => CertificateStatus::Active,
        ]);

        $expired = Certificate::query()->create([
            'product_id' => $this->createProduct()->id,
            'certificate_type_id' => $this->createCertificateType()->id,
            'issuer_id' => $this->createIssuer()->id,
            'certificate_number' => 'CERT-EXPIRED-001',
            'issued_at' => '2026-01-01',
            'expires_at' => '2026-04-01',
            'status' => CertificateStatus::Active,
        ]);

        $this->assertSame(CertificateStatus::Active, $active->currentStatus());
        $this->assertSame(CertificateStatus::ExpiringSoon, $expiringSoon->currentStatus());
        $this->assertSame(CertificateStatus::Expired, $expired->currentStatus());

        $this->assertSame(1, Certificate::query()->monitoringActive()->count());
        $this->assertSame(1, Certificate::query()->monitoringExpiringSoon()->count());
        $this->assertSame(1, Certificate::query()->monitoringExpired()->count());
        $this->assertSame([
            'total' => 3,
            'active' => 1,
            'expiring_soon' => 1,
            'expired' => 1,
        ], Certificate::monitoringSummary());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function createProduct(): Product
    {
        $code = 'PRD-'.str()->upper(str()->random(6));
        $name = 'Produk '.str()->random(5);

        return Product::query()->create([
            'category_id' => $this->createCategory()->id,
            'code' => $code,
            'name' => $name,
            'slug' => str($name)->slug()->append('-'.str()->lower(str()->random(4))),
            'description' => 'Produk pengujian monitoring status.',
            'notes' => null,
            'is_active' => true,
        ]);
    }

    private function createCategory(): Category
    {
        $name = 'Kategori '.str()->random(5);

        return Category::query()->create([
            'name' => $name,
            'slug' => str($name)->slug()->append('-'.str()->lower(str()->random(4))),
            'description' => 'Kategori pengujian monitoring.',
            'is_active' => true,
        ]);
    }

    private function createCertificateType(): CertificateType
    {
        $name = 'Jenis '.str()->random(5);

        return CertificateType::query()->create([
            'name' => $name,
            'slug' => str($name)->slug()->append('-'.str()->lower(str()->random(4))),
            'description' => 'Jenis sertifikat pengujian monitoring.',
            'renewal_period_days' => 365,
            'is_active' => true,
        ]);
    }

    private function createIssuer(): Issuer
    {
        $code = 'ISS-'.str()->upper(str()->random(4));

        return Issuer::query()->create([
            'name' => 'Issuer '.str()->random(5),
            'code' => $code,
            'contact_person' => 'PIC Monitoring',
            'email' => str()->lower($code).'@example.test',
            'phone' => '08123456789',
            'website' => 'https://example.test',
            'address' => 'Jakarta',
            'notes' => null,
            'is_active' => true,
        ]);
    }
}
