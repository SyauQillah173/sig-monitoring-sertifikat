<?php

namespace Tests\Feature\Cement;

use App\Enums\UserRole;
use App\Models\SertifikatSistemAuditEvent;
use App\Models\SertifikatSistemSemen;
use App\Models\SertifikatSni;
use App\Models\StoredFile;
use App\Models\User;
use Database\Seeders\CementMonitoringSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CementCertificatePrivateDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_download_private_cement_certificate_file(): void
    {
        Storage::fake('local');
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);
        $certificate = $this->certificateWithPrivateFile();

        $this->get(route('cement.certificates.download', ['type' => 'sni', 'certificate' => $certificate]))
            ->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_download_private_cement_certificate_file_and_audit_is_logged(): void
    {
        Storage::fake('local');
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $certificate = $this->certificateWithPrivateFile();
        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $response = $this->actingAs($petugas)
            ->get(route('cement.certificates.download', ['type' => 'sni', 'certificate' => $certificate]));

        $response->assertOk();
        $response->assertHeader('content-disposition');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $petugas->id,
            'auditable_type' => SertifikatSni::class,
            'auditable_id' => $certificate->id,
            'action' => 'cement_certificate_downloaded',
        ]);
    }

    public function test_authorized_user_can_download_database_backed_certificate_file(): void
    {
        config(['filesystems.certificate_files.driver' => 'database']);
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $certificate = SertifikatSni::query()->firstOrFail();
        $certificate->forceFill([
            'file_sertifikat' => 'uploads/sertifikat/database-sni.pdf',
        ])->save();

        StoredFile::query()->create([
            'path' => $certificate->file_sertifikat,
            'original_name' => 'database-sni.pdf',
            'mime_type' => 'application/pdf',
            'size' => strlen('database-certificate'),
            'contents' => 'database-certificate',
        ]);

        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $response = $this->actingAs($petugas)
            ->get(route('cement.certificates.download', ['type' => 'sni', 'certificate' => $certificate]));

        $response->assertOk()
            ->assertHeader('content-disposition');
        $this->assertSame('database-certificate', $response->streamedContent());
    }

    public function test_guest_cannot_download_system_iso_certificate_file(): void
    {
        Storage::fake('local');
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $certificate = $this->systemCertificateWithPrivateFile();

        $this->get(route('cement.certificates.download', ['type' => 'system', 'certificate' => $certificate]))
            ->assertRedirect(route('login'));
    }

    public function test_authorized_system_iso_certificate_download_is_audit_logged(): void
    {
        Storage::fake('local');
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $certificate = $this->systemCertificateWithPrivateFile();
        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);

        $this->actingAs($admin)
            ->get(route('cement.certificates.download', ['type' => 'system', 'certificate' => $certificate]))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'auditable_type' => SertifikatSistemSemen::class,
            'auditable_id' => $certificate->id,
            'action' => 'cement_system_certificate_downloaded',
        ]);
    }

    public function test_guest_cannot_download_system_iso_audit_evidence_file(): void
    {
        Storage::fake('local');
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $event = $this->systemAuditEventWithEvidenceFile();

        $this->get(route('cement.system-audit-evidence.download', $event))
            ->assertRedirect(route('login'));
    }

    public function test_authorized_system_iso_audit_evidence_download_is_audit_logged(): void
    {
        Storage::fake('local');
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $event = $this->systemAuditEventWithEvidenceFile();
        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $this->actingAs($petugas)
            ->get(route('cement.system-audit-evidence.download', $event))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $petugas->id,
            'auditable_type' => SertifikatSistemAuditEvent::class,
            'auditable_id' => $event->id,
            'action' => 'cement_system_audit_evidence_downloaded',
        ]);
    }

    public function test_cement_product_page_does_not_expose_public_storage_certificate_url(): void
    {
        Storage::fake('local');
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $this->certificateWithPrivateFile();
        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $this->actingAs($petugas)
            ->get(route('cement.products.index'))
            ->assertOk()
            ->assertDontSee('/storage/uploads/sertifikat', false)
            ->assertSee('/sertifikat-semen/sni/', false);
    }

    public function test_cement_product_tables_use_internal_scroll_wrappers(): void
    {
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $response = $this->actingAs($petugas)
            ->get(route('cement.products.index'))
            ->assertOk()
            ->assertSee('ui-cement-table-scroll', false);

        $this->assertSame(3, substr_count($response->getContent(), 'ui-cement-table-scroll'));
    }

    public function test_cement_certificate_download_is_rate_limited(): void
    {
        Storage::fake('local');
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $certificate = $this->certificateWithPrivateFile();
        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $route = route('cement.certificates.download', ['type' => 'sni', 'certificate' => $certificate]);

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->actingAs($admin)->get($route)->assertOk();
        }

        $this->actingAs($admin)->get($route)->assertTooManyRequests();
    }

    private function certificateWithPrivateFile(): SertifikatSni
    {
        $certificate = SertifikatSni::query()->firstOrFail();
        $certificate->forceFill([
            'file_sertifikat' => 'uploads/sertifikat/private-sni.pdf',
        ])->save();

        Storage::disk('local')->put($certificate->file_sertifikat, 'private-certificate');

        return $certificate;
    }

    private function systemCertificateWithPrivateFile(): SertifikatSistemSemen
    {
        $certificate = SertifikatSistemSemen::query()->with(['isoStandard', 'lokasiPabrik'])->firstOrFail();
        $certificate->forceFill([
            'file_sertifikat' => 'uploads/sertifikat-sistem/private-iso.pdf',
        ])->save();

        Storage::disk('local')->put($certificate->file_sertifikat, 'private-system-certificate');

        return $certificate;
    }

    private function systemAuditEventWithEvidenceFile(): SertifikatSistemAuditEvent
    {
        $certificate = SertifikatSistemSemen::query()->firstOrFail();
        $event = SertifikatSistemAuditEvent::query()
            ->where('sertifikat_sistem_semen_id', $certificate->id)
            ->firstOrFail();

        $event->forceFill([
            'target_date' => now()->toDateString(),
            'completed_at' => now()->toDateString(),
            'status' => SertifikatSistemAuditEvent::STATUS_COMPLETED,
            'evidence_file' => 'uploads/sertifikat-sistem/audit-evidence.pdf',
            'notes' => 'Bukti audit internal.',
        ])->save();

        Storage::disk('local')->put($event->evidence_file, 'private-audit-evidence');

        return $event;
    }
}
