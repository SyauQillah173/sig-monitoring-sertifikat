<?php

namespace App\Models;

use App\Models\Concerns\AuditsModelChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IsoStandard extends Model
{
    use AuditsModelChanges;

    protected $fillable = [
        'code',
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function sertifikatSistemSemen(): HasMany
    {
        return $this->hasMany(SertifikatSistemSemen::class);
    }
}
