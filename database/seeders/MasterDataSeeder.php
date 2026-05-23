<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            CertificateTypeSeeder::class,
            IssuerSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
