<?php

namespace Tests\Feature\Security;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_sent_on_public_pages(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('Content-Security-Policy')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
    }

    public function test_admin_without_two_factor_is_redirected_to_security_settings_when_enforced(): void
    {
        config(['security.enforce_admin_2fa' => true]);

        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('security.edit'));
    }

    public function test_admin_can_access_two_factor_setup_page_when_enforcement_is_enabled(): void
    {
        config(['security.enforce_admin_2fa' => true]);

        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'))
            ->assertOk();
    }
}
