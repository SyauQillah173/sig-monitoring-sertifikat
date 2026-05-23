<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\CertificateType;
use App\Models\Issuer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_access_master_data_pages(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $response = $this->actingAs($petugas)->get(route('admin.categories.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_create_category(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Produk Beku',
            'slug' => 'produk-beku',
            'description' => 'Kategori produk beku.',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'name' => 'Produk Beku',
            'slug' => 'produk-beku',
        ]);
    }

    public function test_admin_can_update_certificate_type(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $certificateType = CertificateType::query()->create([
            'name' => 'Sertifikat Mutu',
            'slug' => 'sertifikat-mutu',
            'description' => 'Deskripsi awal',
            'renewal_period_days' => 365,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.certificate-types.update', $certificateType), [
            'name' => 'Sertifikat Mutu Produk',
            'slug' => 'sertifikat-mutu-produk',
            'description' => 'Deskripsi diperbarui',
            'renewal_period_days' => 730,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.certificate-types.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('certificate_types', [
            'id' => $certificateType->id,
            'name' => 'Sertifikat Mutu Produk',
            'slug' => 'sertifikat-mutu-produk',
            'renewal_period_days' => 730,
        ]);
    }

    public function test_admin_can_delete_issuer(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $issuer = Issuer::query()->create([
            'name' => 'Lembaga Sertifikasi Daerah',
            'code' => 'LSD',
            'contact_person' => 'PIC',
            'email' => 'lsd@example.test',
            'phone' => '021888888',
            'website' => 'https://lsd.test',
            'address' => 'Bandung',
            'notes' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.issuers.destroy', $issuer));

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('issuers', [
            'id' => $issuer->id,
        ]);
    }

    public function test_category_form_validation_is_applied(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        Category::query()->create([
            'name' => 'Kategori Lama',
            'slug' => 'kategori-lama',
            'description' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->from(route('admin.categories.create'))->post(route('admin.categories.store'), [
            'name' => '',
            'slug' => 'kategori-lama',
            'description' => null,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.categories.create'))
            ->assertSessionHasErrors(['name', 'slug']);
    }
}
