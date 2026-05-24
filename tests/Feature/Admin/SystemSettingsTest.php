<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\NavigationItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_system_settings_pages_and_petugas_is_forbidden(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $this->actingAs($admin)
            ->get(route('system-settings.index'))
            ->assertOk()
            ->assertSee('Pengaturan Sistem');

        $this->actingAs($admin)
            ->get(route('system-settings.public-appearance.edit'))
            ->assertOk()
            ->assertSee('Tampilan Publik');

        $this->actingAs($admin)
            ->get(route('system-settings.navigation.index'))
            ->assertOk()
            ->assertSee('Menu Aplikasi');

        $this->actingAs($petugas)
            ->get(route('system-settings.index'))
            ->assertForbidden();
    }

    public function test_admin_can_update_public_landing_text(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);

        $this->actingAs($admin)
            ->put(route('system-settings.public-appearance.update'), [
                'public_brand_kicker' => 'CMS Preview',
                'public_brand_name' => 'SIG CMS',
                'landing_badge' => 'Badge CMS',
                'landing_title' => 'Judul landing dari CMS.',
                'landing_description' => 'Deskripsi landing berhasil diatur melalui pengaturan sistem.',
                'landing_value_1_title' => 'Value Satu',
                'landing_value_1_body' => 'Isi value satu.',
                'landing_value_2_title' => 'Value Dua',
                'landing_value_2_body' => 'Isi value dua.',
                'landing_value_3_title' => 'Value Tiga',
                'landing_value_3_body' => 'Isi value tiga.',
                'footer_text' => 'Footer CMS',
            ])
            ->assertRedirect(route('system-settings.public-appearance.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('system_settings', [
            'key' => 'landing_title',
            'value' => 'Judul landing dari CMS.',
        ]);

        auth()->guard()->logout();
        $this->flushSession();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('SIG CMS')
            ->assertSee('Judul landing dari CMS.')
            ->assertSee('Footer CMS');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Footer CMS');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Footer CMS');
    }

    public function test_sidebar_uses_fallback_when_navigation_table_is_empty(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);

        NavigationItem::query()->delete();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Keamanan Akun')
            ->assertSee('Pengaturan Sistem')
            ->assertSee('Manajemen User');
    }

    public function test_security_settings_navigation_item_is_folded_into_account_settings(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        NavigationItem::query()->create([
            'group_label' => 'Platform',
            'label' => 'Keamanan Akun',
            'route_name' => 'security.edit',
            'icon' => 'shield-check',
            'sort_order' => 31,
            'allowed_roles' => UserRole::values(),
            'is_active' => true,
        ]);

        $this->actingAs($petugas)
            ->get(route('petugas.dashboard'))
            ->assertOk()
            ->assertSee('Pengaturan Akun')
            ->assertDontSee('Keamanan Akun');

        $this->assertDatabaseHas('navigation_items', [
            'route_name' => 'security.edit',
        ]);
    }

    public function test_admin_can_update_navigation_visibility_by_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $this->actingAs($admin)->get(route('system-settings.navigation.index'))->assertOk();
        $export = NavigationItem::query()->where('route_name', 'cement.exports.index')->firstOrFail();

        $items = NavigationItem::query()
            ->ordered()
            ->get()
            ->map(fn (NavigationItem $item) => [
                'id' => $item->id,
                'group_label' => $item->group_label,
                'label' => $item->label,
                'icon' => $item->icon,
                'sort_order' => $item->sort_order,
                'allowed_roles' => $item->is($export) ? [UserRole::Admin->value] : ($item->allowed_roles ?? []),
                'is_active' => $item->is_active ? '1' : '0',
            ])
            ->all();

        $this->actingAs($admin)
            ->put(route('system-settings.navigation.update'), ['items' => $items])
            ->assertRedirect(route('system-settings.navigation.index'));

        $this->actingAs($petugas)
            ->get(route('petugas.dashboard'))
            ->assertOk()
            ->assertDontSee('Export Data');
    }

    public function test_navigation_route_name_is_fixed_and_not_updated_from_form(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);

        $this->actingAs($admin)->get(route('system-settings.navigation.index'))->assertOk();

        $product = NavigationItem::query()->where('route_name', 'cement.products.index')->firstOrFail();

        $items = NavigationItem::query()
            ->ordered()
            ->get()
            ->map(fn (NavigationItem $item) => [
                'id' => $item->id,
                'group_label' => $item->group_label,
                'label' => $item->label,
                'route_name' => $item->is($product) ? 'admin.users.index' : $item->route_name,
                'icon' => $item->icon,
                'sort_order' => $item->sort_order,
                'allowed_roles' => $item->allowed_roles ?? [],
                'is_active' => $item->is_active ? '1' : '0',
            ])
            ->all();

        $this->actingAs($admin)
            ->put(route('system-settings.navigation.update'), ['items' => $items])
            ->assertRedirect(route('system-settings.navigation.index'));

        $this->assertDatabaseHas('navigation_items', [
            'id' => $product->id,
            'route_name' => 'cement.products.index',
        ]);
    }

    public function test_navigation_icon_must_use_available_system_options(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);

        $this->actingAs($admin)->get(route('system-settings.navigation.index'))->assertOk();

        $items = NavigationItem::query()
            ->ordered()
            ->get()
            ->map(fn (NavigationItem $item) => [
                'id' => $item->id,
                'group_label' => $item->group_label,
                'label' => $item->label,
                'icon' => 'icon-ngawur',
                'sort_order' => $item->sort_order,
                'allowed_roles' => $item->allowed_roles ?? [],
                'is_active' => $item->is_active ? '1' : '0',
            ])
            ->all();

        $this->actingAs($admin)
            ->put(route('system-settings.navigation.update'), ['items' => $items])
            ->assertSessionHasErrors('items.0.icon');
    }
}
