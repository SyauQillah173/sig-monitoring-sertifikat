<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\DatabaseBackup;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SystemBackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_admin_can_access_backup_page_and_petugas_is_forbidden(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $this->actingAs($admin)
            ->get(route('system-settings.backups.index'))
            ->assertOk()
            ->assertSee('Backup &amp; Maintenance', false);

        $this->actingAs($petugas)
            ->get(route('system-settings.backups.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_download_and_cleanup_backup(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);

        $this->actingAs($admin)
            ->post(route('system-settings.backups.store'))
            ->assertRedirect(route('system-settings.backups.index'))
            ->assertSessionHas('success');

        $backup = DatabaseBackup::query()->where('status', DatabaseBackup::STATUS_SUCCESS)->firstOrFail();

        Storage::disk('local')->assertExists($backup->path);

        $this->actingAs($admin)
            ->get(route('system-settings.backups.download', $backup))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'auditable_type' => DatabaseBackup::class,
            'auditable_id' => $backup->id,
            'action' => 'system_backup_downloaded',
        ]);

        $this->actingAs($admin)
            ->post(route('system-settings.backups.cleanup'))
            ->assertRedirect(route('system-settings.backups.index'))
            ->assertSessionHas('success');
    }

    public function test_admin_can_update_backup_settings(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);

        $this->actingAs($admin)
            ->put(route('system-settings.backups.update'), [
                'backup_auto_enabled' => '0',
                'backup_include_private_files' => '0',
                'backup_retention_days' => 30,
                'backup_max_count' => 5,
            ])
            ->assertRedirect(route('system-settings.backups.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('system_settings', [
            'key' => 'backup_retention_days',
            'value' => '30',
            'group' => 'system_backup',
        ]);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'backup_auto_enabled',
            'value' => '0',
            'group' => 'system_backup',
        ]);
    }

    public function test_scheduled_backup_command_creates_backup_when_forced(): void
    {
        $this->artisan('system:backup', ['--force' => true])
            ->assertSuccessful();

        $backup = DatabaseBackup::query()->where('triggered_by', DatabaseBackup::TRIGGER_SCHEDULED)->firstOrFail();

        $this->assertSame(DatabaseBackup::STATUS_SUCCESS, $backup->status);
        Storage::disk('local')->assertExists($backup->path);
    }
}
