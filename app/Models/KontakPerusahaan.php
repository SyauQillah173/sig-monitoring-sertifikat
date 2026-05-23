<?php

namespace App\Models;

use App\Models\Concerns\AuditsModelChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KontakPerusahaan extends Model
{
    use AuditsModelChanges;

    protected $table = 'kontak_perusahaan';

    protected $fillable = [
        'perusahaan_semen_id',
        'nama_pic',
        'jabatan',
        'email',
        'phone',
        'is_primary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function perusahaanSemen(): BelongsTo
    {
        return $this->belongsTo(PerusahaanSemen::class);
    }
}
