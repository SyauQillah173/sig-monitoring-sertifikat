<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            'PwrPro' => 'Semen Hidraulis',
            'Dynamix Masonry' => 'Semen Masonry',
            'SprintPro' => 'Semen Portland (OPC)',
            'UltraPro' => 'Semen Portland (OPC)',
            'Semen Merdeka' => 'Semen Portland Kombinasi',
            'Dynamix' => 'Semen Portland Komposit (PCC)',
            'Dynamix Extra Power' => 'Semen Portland Komposit (PCC)',
            'EzPro' => 'Semen Portland Komposit (PCC)',
            'Semen Gresik' => 'Semen Portland Komposit (PCC)',
            'Semen Padang' => 'Semen Portland Komposit (PCC)',
            'Semen Tonasa' => 'Semen Portland Komposit (PCC)',
            'DuPro - LH' => 'Semen Portland Pozzolan (PPC)',
            'DuPro - SBC' => 'Semen Portland Pozzolan (PPC)',
            'MAXSTRENGTH PRO' => 'Semen Portland Slag',
        ])->each(function (string $categoryName, string $brandName) {
            $category = Category::query()->firstOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName, 'is_active' => true],
            );

            Product::query()->updateOrCreate(
                ['slug' => Str::slug($brandName)],
                [
                    'category_id' => $category->id,
                    'code' => 'SMN-'.Str::upper(Str::slug($brandName, '-')),
                    'name' => $brandName,
                    'description' => 'Merek produk semen untuk monitoring sertifikat.',
                    'is_active' => true,
                ],
            );
        });
    }
}
