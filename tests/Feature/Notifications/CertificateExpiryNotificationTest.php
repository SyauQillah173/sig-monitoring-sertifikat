<?php

namespace Tests\Feature\Notifications;

use App\Enums\NotificationStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Issuer;
use App\Models\Notification;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CertificateExpiryNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_generates_internal_notifications_for_certificates_expiring_within_30_days(): void
    {
        Carbon::setTestNow('2026-04-19 09:00:00');
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $dependencies = $this->createCertificateDependencies();

        Certificate::query()->create([
            'product_id' => $dependencies['product']->id,
            'certificate_type_id' => $dependencies['certificateType']->id,
            'issuer_id' => $dependencies['issuer']->id,
            'issued_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
            'certificate_number' => 'CERT-NOTIF-001',
            'issued_at' => '2026-01-01',
            'expires_at' => '2026-05-05',
            'status' => 'active',
        ]);

        Certificate::query()->create([
            'product_id' => $dependencies['product']->id,
            'certificate_type_id' => $dependencies['certificateType']->id,
            'issuer_id' => $dependencies['issuer']->id,
            'issued_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
            'certificate_number' => 'CERT-NOTIF-002',
            'issued_at' => '2026-01-01',
            'expires_at' => '2026-06-30',
            'status' => 'active',
        ]);

        $this->artisan('notifications:generate-certificate-expiry')
            ->expectsOutput('Notifikasi kedaluwarsa sertifikat selesai diproses.')
            ->assertSuccessful();

        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'notification_type' => 'certificate_expiry_reminder',
            'status' => NotificationStatus::Unread->value,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $petugas->id,
            'notification_type' => 'certificate_expiry_reminder',
            'status' => NotificationStatus::Unread->value,
        ]);
    }

    public function test_notification_can_be_marked_as_read_and_unread_badge_is_visible(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create()->assignAppRole(UserRole::Admin);

        $notification = Notification::query()->create([
            'user_id' => $user->id,
            'certificate_id' => null,
            'title' => 'Sertifikat akan habis masa berlaku',
            'message' => 'Segera tindak lanjuti sertifikat.',
            'notification_type' => 'certificate_expiry_reminder',
            'status' => NotificationStatus::Unread,
            'scheduled_at' => now(),
            'sent_at' => now(),
        ]);

        $dashboardResponse = $this->actingAs($user)->get(route('admin.dashboard'));

        $dashboardResponse->assertOk()
            ->assertSee('Notifikasi')
            ->assertSee('1');

        $response = $this->actingAs($user)->patch(route('notifications.read', $notification));

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => NotificationStatus::Read->value,
        ]);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create()->assignAppRole(UserRole::Petugas);

        Notification::query()->create([
            'user_id' => $user->id,
            'certificate_id' => null,
            'title' => 'Reminder 1',
            'message' => 'Pesan 1',
            'notification_type' => 'certificate_expiry_reminder',
            'status' => NotificationStatus::Unread,
            'scheduled_at' => now(),
            'sent_at' => now(),
        ]);

        Notification::query()->create([
            'user_id' => $user->id,
            'certificate_id' => null,
            'title' => 'Reminder 2',
            'message' => 'Pesan 2',
            'notification_type' => 'certificate_expiry_reminder',
            'status' => NotificationStatus::Unread,
            'scheduled_at' => now(),
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($user)->patch(route('notifications.read-all'));

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(0, $user->fresh()->unreadSystemNotifications()->count());
    }

    public function test_read_notifications_older_than_90_days_are_hidden_from_internal_inbox(): void
    {
        Carbon::setTestNow('2026-05-25 10:00:00');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create()->assignAppRole(UserRole::Admin);

        Notification::query()->create([
            'user_id' => $user->id,
            'certificate_id' => null,
            'title' => 'Notifikasi lama',
            'message' => 'Pesan lama.',
            'notification_type' => 'certificate_expiry_reminder',
            'status' => NotificationStatus::Read,
            'scheduled_at' => now()->subDays(100),
            'sent_at' => now()->subDays(100),
            'read_at' => now()->subDays(91),
        ]);

        Notification::query()->create([
            'user_id' => $user->id,
            'certificate_id' => null,
            'title' => 'Notifikasi baru dibaca',
            'message' => 'Pesan baru.',
            'notification_type' => 'certificate_expiry_reminder',
            'status' => NotificationStatus::Read,
            'scheduled_at' => now()->subDays(30),
            'sent_at' => now()->subDays(30),
            'read_at' => now()->subDays(30),
        ]);

        Notification::query()->create([
            'user_id' => $user->id,
            'certificate_id' => null,
            'title' => 'Notifikasi belum dibaca',
            'message' => 'Pesan belum dibaca.',
            'notification_type' => 'certificate_expiry_reminder',
            'status' => NotificationStatus::Unread,
            'scheduled_at' => now()->subDays(120),
            'sent_at' => now()->subDays(120),
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertDontSee('Notifikasi lama')
            ->assertSee('Notifikasi baru dibaca')
            ->assertSee('Notifikasi belum dibaca');
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
            'name' => 'Notif Category',
            'slug' => 'notif-category',
            'description' => null,
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'code' => 'PRD-NOTIF-001',
            'name' => 'Notif Product',
            'slug' => 'notif-product',
            'description' => null,
            'notes' => null,
            'is_active' => true,
        ]);

        $certificateType = CertificateType::query()->create([
            'name' => 'Notif Type',
            'slug' => 'notif-type',
            'description' => null,
            'renewal_period_days' => 365,
            'is_active' => true,
        ]);

        $issuer = Issuer::query()->create([
            'name' => 'Notif Issuer',
            'code' => 'NTF',
            'contact_person' => 'PIC',
            'email' => 'notif-issuer@example.test',
            'phone' => '08111111999',
            'website' => 'https://notif-issuer.example.test',
            'address' => 'Bandung',
            'notes' => null,
            'is_active' => true,
        ]);

        return compact('product', 'certificateType', 'issuer');
    }
}
