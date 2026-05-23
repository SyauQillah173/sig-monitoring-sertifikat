<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurgeLegacyCertificateDataSeeder extends Seeder
{
    /**
     * Remove old generic certificate demo data while keeping users, roles, and
     * the cement monitoring tables intact.
     */
    public function run(): void
    {
        $tables = [
            'audit_logs',
            'notifications',
            'certificate_renewals',
            'certificates',
            'products',
            'categories',
            'certificate_types',
            'issuers',
        ];

        Schema::disableForeignKeyConstraints();

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();
    }
}
