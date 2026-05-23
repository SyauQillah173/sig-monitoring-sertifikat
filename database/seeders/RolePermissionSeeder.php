<?php

namespace Database\Seeders;

use App\Enums\AppPermission;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AppPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        $admin = Role::findOrCreate(UserRole::Admin->value, 'web');
        $petugas = Role::findOrCreate(UserRole::Petugas->value, 'web');

        $admin->syncPermissions(AppPermission::values());

        $petugas->syncPermissions([
            AppPermission::DashboardView->value,
            AppPermission::MasterDataView->value,
            AppPermission::CertificatesView->value,
            AppPermission::CertificatesManage->value,
        ]);

        Role::query()
            ->where('name', 'pimpinan')
            ->where('guard_name', 'web')
            ->delete();
    }
}
