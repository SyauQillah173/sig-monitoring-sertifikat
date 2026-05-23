<?php

namespace Database\Seeders;

use App\Models\Issuer;
use Illuminate\Database\Seeder;

class IssuerSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['name' => 'B4T', 'code' => 'B4T'],
            ['name' => 'LSPro-B4T Bandung', 'code' => 'LSPRO-B4T'],
            ['name' => 'BSI (Balai Sertifikasi Industri)', 'code' => 'BSI'],
        ])->each(fn (array $payload) => Issuer::query()->updateOrCreate(
            ['code' => $payload['code']],
            [...$payload, 'notes' => 'Lembaga sertifikasi produk semen.', 'is_active' => true],
        ));
    }
}
