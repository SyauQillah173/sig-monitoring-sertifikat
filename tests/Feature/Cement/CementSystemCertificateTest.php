<?php

namespace Tests\Feature\Cement;

use App\Enums\UserRole;
use App\Mail\CementCertificateReminderMail;
use App\Models\IsoStandard;
use App\Models\LokasiPabrik;
use App\Models\Notification;
use App\Models\SertifikatSistemAuditEvent;
use App\Models\SertifikatSistemSemen;
use App\Models\User;
use Database\Seeders\CementMonitoringSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CementSystemCertificateTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_iso_system_certificates_are_shown_on_system_dashboard(): void
    {
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $user = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $this->actingAs($user)
            ->get(route('cement.system.index'))
            ->assertOk()
            ->assertSee('ISO 9001')
            ->assertSee('Sistem Manajemen Mutu')
            ->assertSee('ISO 27001')
            ->assertSee('Surveilen 2');
    }

    public function test_admin_can_create_system_iso_certificate(): void
    {
        Storage::fake('local');
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $standard = IsoStandard::query()->where('code', 'ISO 9001')->firstOrFail();
        $location = LokasiPabrik::query()->firstOrFail();

        $response = $this->actingAs($admin)
            ->post(route('cement.maintenance.sertifikat-sistem.store'), [
                'lokasi_pabrik_id' => $location->id,
                'iso_standard_id' => $standard->id,
                'certificate_number' => 'ISO-9001-TEST-NEW',
                'issuer' => 'Test Issuer',
                'audit_stage' => SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_1,
                'scope' => 'Produksi semen',
                'issued_at' => '2026-01-01',
                'berlaku_sd' => '2029-01-01',
                'acquisition_year' => 2026,
                'certification_level' => SertifikatSistemSemen::LEVEL_INTERNASIONAL,
                'certification_category' => 'Sistem Manajemen Mutu',
                'process_owner' => 'QA Management System',
                'accreditation_number' => 'KAN-ISO-TEST',
                'public_url' => 'https://example.test/iso-9001',
                'description' => 'Deskripsi korporasi sertifikat ISO.',
                'file_sertifikat' => UploadedFile::fake()->create('iso-9001.pdf', 128, 'application/pdf'),
            ]);

        $certificate = SertifikatSistemSemen::query()->where('certificate_number', 'ISO-9001-TEST-NEW')->firstOrFail();

        $response->assertRedirect(route('cement.maintenance.sertifikat-sistem.show', $certificate));
        $this->assertDatabaseHas('sertifikat_sistem_semen', [
            'certificate_number' => 'ISO-9001-TEST-NEW',
            'lokasi_pabrik_id' => $location->id,
            'iso_standard_id' => $standard->id,
            'acquisition_year' => 2026,
            'certification_level' => SertifikatSistemSemen::LEVEL_INTERNASIONAL,
            'process_owner' => 'QA Management System',
        ]);
        $this->assertDatabaseHas('sertifikat_sistem_audit_events', [
            'sertifikat_sistem_semen_id' => $certificate->id,
            'audit_type' => SertifikatSistemAuditEvent::TYPE_INITIAL,
            'status' => SertifikatSistemAuditEvent::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseHas('sertifikat_sistem_audit_events', [
            'sertifikat_sistem_semen_id' => $certificate->id,
            'audit_type' => SertifikatSistemAuditEvent::TYPE_SURVEILEN_1,
            'status' => SertifikatSistemAuditEvent::STATUS_PENDING,
        ]);
        Storage::disk('local')->assertExists($certificate->file_sertifikat);
    }

    public function test_system_dashboard_shows_every_iso_location_certificate(): void
    {
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $standard = IsoStandard::query()->where('code', 'ISO 9001')->firstOrFail();
        $location = LokasiPabrik::query()->create([
            'nama_lokasi' => 'Pabrik Rembang',
            'is_active' => true,
        ]);

        SertifikatSistemSemen::query()->create([
            'lokasi_pabrik_id' => $location->id,
            'iso_standard_id' => $standard->id,
            'certificate_number' => 'ISO-9001-RMB-2026',
            'issuer' => 'Issuer Rembang',
            'audit_stage' => SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_1,
            'scope' => 'Produksi semen Rembang',
            'issued_at' => '2026-01-01',
            'berlaku_sd' => '2029-01-01',
            'acquisition_year' => 2026,
            'certification_level' => SertifikatSistemSemen::LEVEL_INTERNASIONAL,
        ]);

        $this->actingAs($admin)
            ->get(route('cement.system.index'))
            ->assertOk()
            ->assertSee('Pabrik Tuban')
            ->assertSee('Pabrik Rembang')
            ->assertSee('ISO 9001');
    }

    public function test_system_iso_certificate_uses_private_download_route(): void
    {
        Storage::fake('local');
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $certificate = SertifikatSistemSemen::query()->firstOrFail();
        $certificate->forceFill(['file_sertifikat' => 'uploads/sertifikat-sistem/private-iso.pdf'])->save();
        Storage::disk('local')->put($certificate->file_sertifikat, 'private-system-certificate');

        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $this->actingAs($petugas)
            ->get(route('cement.system.index'))
            ->assertOk()
            ->assertDontSee('/storage/uploads/sertifikat-sistem', false)
            ->assertSee('/sertifikat-semen/system/', false);

        $this->actingAs($petugas)
            ->get(route('cement.certificates.download', ['type' => 'system', 'certificate' => $certificate]))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_system_iso_follow_up_notifications_are_generated_for_admin_and_petugas(): void
    {
        Carbon::setTestNow('2025-12-15 08:00:00');
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $certificate = SertifikatSistemSemen::query()->firstOrFail();
        $certificate->forceFill([
            'audit_stage' => SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_1,
            'issued_at' => '2025-01-01',
            'berlaku_sd' => '2028-01-01',
        ])->save();

        $this->artisan('notifications:generate-certificate-expiry')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'notification_type' => 'cement_system_follow_up',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $petugas->id,
            'notification_type' => 'cement_system_follow_up',
        ]);
    }

    public function test_manual_reminder_trigger_sends_email_and_generates_internal_notifications(): void
    {
        Mail::fake();
        Carbon::setTestNow('2025-12-15 08:00:00');
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $certificate = SertifikatSistemSemen::query()->firstOrFail();
        $certificate->forceFill([
            'audit_stage' => SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_1,
            'issued_at' => '2025-01-01',
            'berlaku_sd' => '2028-01-01',
        ])->save();

        $this->actingAs($admin)
            ->post(route('cement.maintenance.notification-settings.send-reminders'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'notification_type' => 'cement_system_follow_up',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $petugas->id,
            'notification_type' => 'cement_system_follow_up',
        ]);
        Mail::assertSent(CementCertificateReminderMail::class, 1);
    }

    public function test_system_iso_follow_up_form_is_protected_by_login_and_role(): void
    {
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $certificate = SertifikatSistemSemen::query()->firstOrFail();
        $certificate->forceFill([
            'audit_stage' => SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_1,
            'issued_at' => '2025-01-01',
            'berlaku_sd' => '2028-01-01',
        ])->save();

        $route = route('cement.system-follow-up.confirm', [
            'certificate' => $certificate,
            'action' => SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_1,
        ]);

        $this->get($route)->assertRedirect(route('login'));

        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $this->actingAs($petugas)->get($route)->assertOk();
    }

    public function test_petugas_can_confirm_surveillance_follow_up_from_notification(): void
    {
        Storage::fake('local');
        Carbon::setTestNow('2025-12-15 08:00:00');
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);
        $certificate = SertifikatSistemSemen::query()->firstOrFail();
        $certificate->forceFill([
            'audit_stage' => SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_1,
            'issued_at' => '2025-01-01',
            'berlaku_sd' => '2028-01-01',
        ])->save();

        $this->artisan('notifications:generate-certificate-expiry')->assertSuccessful();
        $notification = Notification::query()
            ->where('notification_type', 'cement_system_follow_up')
            ->where('user_id', $petugas->id)
            ->firstOrFail();

        $this->actingAs($petugas)
            ->get(route('cement.system-follow-up.confirm', [
                'certificate' => $certificate,
                'action' => SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_1,
            ]))
            ->assertOk()
            ->assertSee('Konfirmasi Surveilen 1');

        $this->actingAs($petugas)
            ->post(route('cement.system-follow-up.store', [
                'certificate' => $certificate,
                'action' => SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_1,
            ]), [
                'completed_at' => '2025-12-15',
                'notes' => 'Surveilen selesai.',
                'evidence_file' => UploadedFile::fake()->create('bukti-surveilen.pdf', 32, 'application/pdf'),
            ])
            ->assertRedirect(route('notifications.index'));

        $this->assertSame(SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_2, $certificate->fresh()->audit_stage);
        $event = $certificate->fresh()->auditEvents()->where('audit_type', SertifikatSistemAuditEvent::TYPE_SURVEILEN_1)->firstOrFail();
        $this->assertSame(SertifikatSistemAuditEvent::STATUS_COMPLETED, $event->status);
        $this->assertSame('2025-12-15', $event->completed_at->toDateString());
        Storage::disk('local')->assertExists($event->evidence_file);
        $this->actingAs($petugas)
            ->get(route('cement.system-audit-evidence.download', $event))
            ->assertOk()
            ->assertHeader('content-disposition');
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_renewal_follow_up_creates_new_system_certificate_with_private_file(): void
    {
        Storage::fake('local');
        Carbon::setTestNow('2026-12-15 08:00:00');
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $certificate = SertifikatSistemSemen::query()->firstOrFail();
        $certificate->forceFill([
            'audit_stage' => SertifikatSistemSemen::AUDIT_STAGE_RENEWAL,
            'issued_at' => '2024-01-01',
            'berlaku_sd' => '2026-12-31',
        ])->save();

        $this->actingAs($admin)
            ->post(route('cement.system-follow-up.store', [
                'certificate' => $certificate,
                'action' => SertifikatSistemSemen::AUDIT_STAGE_RENEWAL,
            ]), [
                'certificate_number' => 'ISO-RENEWAL-FOLLOW-UP',
                'issuer' => 'Renewal Issuer',
                'issued_at' => '2027-01-01',
                'berlaku_sd' => '2030-01-01',
                'file_sertifikat' => UploadedFile::fake()->create('renewal.pdf', 64, 'application/pdf'),
            ])
            ->assertRedirect(route('notifications.index'));

        $newCertificate = SertifikatSistemSemen::query()->where('certificate_number', 'ISO-RENEWAL-FOLLOW-UP')->firstOrFail();

        $this->assertSame($certificate->lokasi_pabrik_id, $newCertificate->lokasi_pabrik_id);
        $this->assertSame($certificate->iso_standard_id, $newCertificate->iso_standard_id);
        $this->assertSame(SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_1, $newCertificate->audit_stage);
        $this->assertDatabaseHas('sertifikat_sistem_audit_events', [
            'sertifikat_sistem_semen_id' => $certificate->id,
            'audit_type' => SertifikatSistemAuditEvent::TYPE_RENEWAL,
            'status' => SertifikatSistemAuditEvent::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseHas('sertifikat_sistem_audit_events', [
            'sertifikat_sistem_semen_id' => $newCertificate->id,
            'audit_type' => SertifikatSistemAuditEvent::TYPE_SURVEILEN_1,
            'status' => SertifikatSistemAuditEvent::STATUS_PENDING,
        ]);
        Storage::disk('local')->assertExists($newCertificate->file_sertifikat);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
