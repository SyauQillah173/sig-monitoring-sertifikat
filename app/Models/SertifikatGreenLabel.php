<?php

namespace App\Models;

use App\Models\Concerns\AuditsModelChanges;
use App\Models\Concerns\HasCertificateExpiryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SertifikatGreenLabel extends Model
{
    use AuditsModelChanges;
    use HasCertificateExpiryStatus;

    protected $table = 'sertifikat_green_label';

    protected $fillable = [
        'merek_semen_id',
        'sni_reference_id',
        'sni',
        'komoditi_reference_id',
        'komoditi',
        'peringkat_green_label_reference_id',
        'peringkat',
        'lokasi_pabrik_id',
        'lokasi',
        'berlaku_sd',
        'file_sertifikat',
    ];

    protected function casts(): array
    {
        return [
            'berlaku_sd' => 'date',
        ];
    }

    public function merekSemen(): BelongsTo
    {
        return $this->belongsTo(MerekSemen::class, 'merek_semen_id');
    }

    public function sniReference(): BelongsTo
    {
        return $this->belongsTo(CementReferenceValue::class, 'sni_reference_id');
    }

    public function komoditiReference(): BelongsTo
    {
        return $this->belongsTo(CementReferenceValue::class, 'komoditi_reference_id');
    }

    public function peringkatGreenLabelReference(): BelongsTo
    {
        return $this->belongsTo(CementReferenceValue::class, 'peringkat_green_label_reference_id');
    }

    public function lokasiPabrik(): BelongsTo
    {
        return $this->belongsTo(LokasiPabrik::class, 'lokasi_pabrik_id');
    }

    public function certificateUrl(): ?string
    {
        return $this->file_sertifikat ? route('cement.certificates.download', ['type' => 'green-label', 'certificate' => $this->getKey()]) : null;
    }
}
