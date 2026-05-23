<?php

namespace Database\Seeders;

use App\Models\CertificateType;
use Illuminate\Database\Seeder;

class CertificateTypeSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['name' => 'Sertifikat SNI', 'slug' => 'sertifikat-sni', 'description' => 'Sertifikat Standar Nasional Indonesia untuk produk semen.'],
            ['name' => 'Sertifikat TKDN', 'slug' => 'sertifikat-tkdn', 'description' => 'Sertifikat Tingkat Komponen Dalam Negeri untuk produk semen.'],
            ['name' => 'Sertifikat Green Label', 'slug' => 'sertifikat-green-label', 'description' => 'Sertifikat Green Label untuk produk semen.'],
        ])->each(fn (array $payload) => CertificateType::query()->updateOrCreate(
            ['slug' => $payload['slug']],
            [...$payload, 'renewal_period_days' => 1095, 'is_active' => true],
        ));
    }
}
