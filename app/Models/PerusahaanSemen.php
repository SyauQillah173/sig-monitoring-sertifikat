<?php

namespace App\Models;

use App\Models\Concerns\AuditsModelChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerusahaanSemen extends Model
{
    use AuditsModelChanges;

    protected $table = 'perusahaan_semen';

    protected $fillable = [
        'nama_perusahaan',
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

    public function kontakPerusahaan(): HasMany
    {
        return $this->hasMany(KontakPerusahaan::class);
    }
}
