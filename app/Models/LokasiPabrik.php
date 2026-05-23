<?php

namespace App\Models;

use App\Models\Concerns\AuditsModelChanges;
use Illuminate\Database\Eloquent\Model;

class LokasiPabrik extends Model
{
    use AuditsModelChanges;

    protected $table = 'lokasi_pabrik';

    protected $fillable = [
        'nama_lokasi',
        'kode',
        'alamat',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
