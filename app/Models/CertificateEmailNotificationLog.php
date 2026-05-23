<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CertificateEmailNotificationLog extends Model
{
    protected $fillable = [
        'certificate_type',
        'certificate_id',
        'kontak_perusahaan_id',
        'recipient_email',
        'notification_type',
        'certificate_expires_at',
        'status',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'certificate_expires_at' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function certificate(): MorphTo
    {
        return $this->morphTo();
    }

    public function kontakPerusahaan(): BelongsTo
    {
        return $this->belongsTo(KontakPerusahaan::class);
    }
}
