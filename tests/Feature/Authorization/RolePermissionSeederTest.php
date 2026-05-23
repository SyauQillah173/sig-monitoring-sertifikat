<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_and_permissions_are_seeded_with_expected_access(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create()->assignAppRole(UserRole::Admin);
        $petugas = User::factory()->create()->assignAppRole(UserRole::Petugas);

        $this->assertTrue($admin->can('users.manage'));
        $this->assertTrue($admin->can('reports.view'));
        $this->assertSame(UserRole::Admin, $admin->fresh()->role);

        $this->assertTrue($petugas->can('certificates.manage'));
        $this->assertFalse($petugas->can('users.manage'));
        $this->assertFalse($petugas->can('reports.view'));
        $this->assertSame(UserRole::Petugas, $petugas->fresh()->role);
    }
}
