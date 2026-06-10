<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use App\Services\SystemNavigationService;
use Database\Seeders\CementMonitoringSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_access_user_management(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Manajemen User');

        $this->actingAs($petugas)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_google_email_login_user(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'User Gmail Test',
                'email' => 'usergmail@example.com',
                'role' => UserRole::Petugas->value,
                'is_active' => '1',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $user = User::query()->where('email', 'usergmail@example.com')->firstOrFail();

        $this->assertTrue($user->hasAppRole(UserRole::Petugas));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_admin_can_update_user_email_and_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $user = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Admin Gmail',
                'email' => 'admin-gmail@example.com',
                'role' => UserRole::Admin->value,
                'is_active' => '0',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('admin-gmail@example.com', $user->email);
        $this->assertTrue($user->hasAppRole(UserRole::Admin));
        $this->assertFalse($user->is_active);
    }

    public function test_admin_can_send_reset_password_link_to_registered_user(): void
    {
        Mail::fake();
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $user = User::factory()->create(['email' => 'reset-target@example.com'])->assignAppRole(UserRole::Petugas);

        $this->actingAs($admin)
            ->post(route('admin.users.send-reset-link', $user))
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(PasswordResetCodeMail::class, fn (PasswordResetCodeMail $mail) => $mail->user->is($user)
            && strlen($mail->code) === 6);
    }

    public function test_full_admin_can_create_limited_admin_with_selected_menu_access(): void
    {
        $this->seed([RolePermissionSeeder::class, CementMonitoringSeeder::class]);

        $fullAdmin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $productMenu = app(SystemNavigationService::class)
            ->accessItems()
            ->firstWhere('route_name', 'cement.products.index');

        $this->actingAs($fullAdmin)
            ->post(route('admin.users.store'), [
                'name' => 'Admin Produk Terbatas',
                'email' => 'admin-produk@example.com',
                'role' => UserRole::Admin->value,
                'is_active' => '1',
                'access_mode' => 'custom',
                'navigation_items' => [$productMenu->id],
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $limitedAdmin = User::query()->where('email', 'admin-produk@example.com')->firstOrFail();

        $this->assertTrue($limitedAdmin->hasAppRole(UserRole::Admin));
        $this->assertTrue($limitedAdmin->has_custom_access);
        $this->assertFalse($limitedAdmin->hasFullSystemAccess());
        $this->assertSame([$productMenu->id], $limitedAdmin->navigationItems()->pluck('navigation_items.id')->all());

        $this->actingAs($limitedAdmin)
            ->get(route('cement.products.index'))
            ->assertOk()
            ->assertSee('Sertifikat Produk')
            ->assertDontSee('Export Data')
            ->assertDontSee('Manajemen User');

        $this->actingAs($limitedAdmin)
            ->get(route('cement.exports.index'))
            ->assertForbidden();

        $this->actingAs($limitedAdmin)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }
}
