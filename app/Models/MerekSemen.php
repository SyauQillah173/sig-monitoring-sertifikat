<?php

namespace App\Models;

use App\Models\Concerns\AuditsModelChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerekSemen extends Model
{
    use AuditsModelChanges;

    protected $table = 'merek_semen';

    protected $fillable = ['kategori_semen_id', 'nama_merek'];

    public function kategoriSemen(): BelongsTo
    {
        return $this->belongsTo(KategoriSemen::class, 'kategori_semen_id');
    }

    public function sertifikatSni(): HasMany
    {
        return $this->hasMany(SertifikatSni::class, 'merek_semen_id');
    }

    public function sertifikatTkdn(): HasMany
    {
        return $this->hasMany(SertifikatTkdn::class, 'merek_semen_id');
    }

    public function sertifikatGreenLabel(): HasMany
    {
        return $this->hasMany(SertifikatGreenLabel::class, 'merek_semen_id');
    }
}
