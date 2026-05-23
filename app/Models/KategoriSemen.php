<?php

namespace App\Models;

use App\Models\Concerns\AuditsModelChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriSemen extends Model
{
    use AuditsModelChanges;

    protected $table = 'kategori_semen';

    protected $fillable = ['nama_kategori'];

    public function merekSemen(): HasMany
    {
        return $this->hasMany(MerekSemen::class, 'kategori_semen_id');
    }
}
