<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $roleIds = $this->pimpinanRoleIds();
        $userIds = Schema::hasTable('users')
            ? DB::table('users')->where('role', 'pimpinan')->pluck('id')->all()
            : [];

        if (Schema::hasTable('model_has_roles')) {
            if ($userIds !== []) {
                DB::table('model_has_roles')
                    ->whereIn('model_id', $userIds)
                    ->where('model_type', 'App\\Models\\User')
                    ->delete();
            }

            if ($roleIds !== []) {
                DB::table('model_has_roles')->whereIn('role_id', $roleIds)->delete();
            }
        }

        if (Schema::hasTable('role_has_permissions') && $roleIds !== []) {
            DB::table('role_has_permissions')->whereIn('role_id', $roleIds)->delete();
        }

        if (Schema::hasTable('users')) {
            DB::table('users')->where('role', 'pimpinan')->delete();
        }

        if (Schema::hasTable('roles')) {
            DB::table('roles')
                ->where('name', 'pimpinan')
                ->where('guard_name', 'web')
                ->delete();
        }

        $this->removePimpinanFromNavigation();
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        DB::table('roles')->updateOrInsert(
            ['name' => 'pimpinan', 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()],
        );

        if (! Schema::hasTable('navigation_items')) {
            return;
        }

        DB::table('navigation_items')
            ->whereIn('route_name', ['cement.products.index', 'cement.system.index', 'profile.edit', 'notifications.index'])
            ->get(['id', 'allowed_roles'])
            ->each(function (object $item): void {
                $roles = $this->decodeRoles($item->allowed_roles);

                if (! in_array('pimpinan', $roles, true)) {
                    $roles[] = 'pimpinan';
                }

                DB::table('navigation_items')
                    ->where('id', $item->id)
                    ->update(['allowed_roles' => json_encode(array_values($roles))]);
            });
    }

    /**
     * @return array<int, int>
     */
    private function pimpinanRoleIds(): array
    {
        if (! Schema::hasTable('roles')) {
            return [];
        }

        return DB::table('roles')
            ->where('name', 'pimpinan')
            ->where('guard_name', 'web')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function removePimpinanFromNavigation(): void
    {
        if (! Schema::hasTable('navigation_items')) {
            return;
        }

        DB::table('navigation_items')
            ->get(['id', 'route_name', 'allowed_roles'])
            ->each(function (object $item): void {
                $roles = $item->route_name === 'cement.exports.index'
                    ? ['admin']
                    : array_values(array_filter(
                        $this->decodeRoles($item->allowed_roles),
                        fn (string $role) => $role !== 'pimpinan',
                    ));

                DB::table('navigation_items')
                    ->where('id', $item->id)
                    ->update(['allowed_roles' => json_encode($roles)]);
            });
    }

    /**
     * @return list<string>
     */
    private function decodeRoles(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, 'is_string'));
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded)
            ? array_values(array_filter($decoded, 'is_string'))
            : [];
    }
};
