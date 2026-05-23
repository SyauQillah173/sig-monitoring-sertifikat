<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        collect([
            'Semen Hidraulis',
            'Semen Masonry',
            'Semen Portland (OPC)',
            'Semen Portland Kombinasi',
            'Semen Portland Komposit (PCC)',
            'Semen Portland Pozzolan (PPC)',
            'Semen Portland Slag',
        ])->each(fn (string $name) => Category::query()->updateOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'description' => 'Kategori produk semen untuk monitoring sertifikat.', 'is_active' => true],
        ));
    }
}
