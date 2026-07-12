<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columns = collect([
            'two_factor_secret',
            'two_factor_recovery_codes',
            'two_factor_confirmed_at',
        ])->filter(fn (string $column): bool => Schema::hasColumn('users', $column))->all();

        if ($columns === []) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->after('password')->nullable();
            }

            if (! Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->after('two_factor_secret')->nullable();
            }

            if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->after('two_factor_recovery_codes')->nullable();
            }
        });
    }
};
