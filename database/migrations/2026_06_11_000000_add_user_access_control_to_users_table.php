<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'is_super_admin')) {
                $table->boolean('is_super_admin')->default(false)->after('is_active')->index();
            }

            if (! Schema::hasColumn('users', 'has_custom_access')) {
                $table->boolean('has_custom_access')->default(false)->after('is_super_admin')->index();
            }
        });

        DB::table('users')
            ->where('role', 'admin')
            ->update([
                'is_super_admin' => true,
                'has_custom_access' => false,
            ]);

        if (! Schema::hasTable('navigation_item_user')) {
            Schema::create('navigation_item_user', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('navigation_item_id')->constrained('navigation_items')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'navigation_item_id'], 'nav_item_user_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_item_user');

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'has_custom_access')) {
                $table->dropColumn('has_custom_access');
            }

            if (Schema::hasColumn('users', 'is_super_admin')) {
                $table->dropColumn('is_super_admin');
            }
        });
    }
};
