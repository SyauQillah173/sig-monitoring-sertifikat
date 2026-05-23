<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('navigation_items')) {
            return;
        }

        DB::table('navigation_items')->updateOrInsert(
            ['route_name' => 'security.edit'],
            [
                'group_label' => 'Platform',
                'label' => 'Keamanan Akun',
                'icon' => 'shield-check',
                'sort_order' => 31,
                'allowed_roles' => json_encode(UserRole::values()),
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('navigation_items')) {
            return;
        }

        DB::table('navigation_items')
            ->where('route_name', 'security.edit')
            ->delete();
    }
};
