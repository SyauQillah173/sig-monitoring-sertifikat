<?php

namespace App\Models;

use App\Enums\CertificateRenewalStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'certificate_id',
    'renewed_by_user_id',
    'renewal_number',
    'previous_certificate_number',
    'new_certificate_number',
    'renewal_date',
    'previous_expires_at',
    'new_expires_at',
    'file_path',
    'status',
    'notes',
])]
class CertificateRenewal extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'renewal_date' => 'date',
            'previous_expires_at' => 'date',
            'new_expires_at' => 'date',
            'status' => CertificateRenewalStatus::class,
        ];
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function renewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renewed_by_user_id');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
