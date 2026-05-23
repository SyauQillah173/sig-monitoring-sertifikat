<?php

namespace Tests\Feature\Cement;

use App\Enums\UserRole;
use App\Models\CementReferenceValue;
use App\Models\MerekSemen;
use App\Models\User;
use Database\Seeders\CementMonitoringSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CementMaintenanceReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_reference_and_notification_pages(): void
    {
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);

        $this->actingAs($admin)
            ->get(route('cement.maintenance.references.index', CementReferenceValue::TYPE_SNI))
            ->assertOk()
            ->assertSee('Data SNI Semen');

        $this->actingAs($admin)
            ->get(route('cement.maintenance.notification-settings.edit'))
            ->assertOk()
            ->assertSee('Pengaturan Email');
    }

    public function test_maintenance_index_tables_use_internal_scroll_wrappers(): void
    {
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);

        $routes = [
            route('cement.maintenance.kategori-semen.index'),
            route('cement.maintenance.merek-semen.index'),
            route('cement.maintenance.lokasi-pabrik.index'),
            route('cement.maintenance.iso-standards.index'),
            route('cement.maintenance.references.index', CementReferenceValue::TYPE_SNI),
            route('cement.maintenance.perusahaan-semen.index'),
            route('cement.maintenance.kontak-perusahaan.index'),
            route('cement.maintenance.sertifikat-sni.index'),
            route('cement.maintenance.sertifikat-tkdn.index'),
            route('cement.maintenance.sertifikat-green-label.index'),
            route('cement.maintenance.sertifikat-sistem.index'),
        ];

        foreach ($routes as $route) {
            $this->actingAs($admin)
                ->get($route)
                ->assertOk()
                ->assertSee('ui-maintenance-table-scroll', false);
        }
    }

    public function test_certificate_form_rejects_unknown_reference_value(): void
    {
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $brand = MerekSemen::query()->firstOrFail();

        $response = $this->actingAs($admin)
            ->from(route('cement.maintenance.sertifikat-sni.create'))
            ->post(route('cement.maintenance.sertifikat-sni.store'), [
                'merek_semen_id' => $brand->id,
                'sni' => 'SNI TYPO BEBAS',
                'komoditi' => 'PCC',
                'jenis_sertifikasi' => 'SPPT SNI',
                'lspro' => 'B4T',
                'lokasi' => 'Pabrik Tuban',
                'berlaku_sd' => '2030-01-01',
            ]);

        $response->assertRedirect(route('cement.maintenance.sertifikat-sni.create'))
            ->assertSessionHasErrors('sni');
    }
}
