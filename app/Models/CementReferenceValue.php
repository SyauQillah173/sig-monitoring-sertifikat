<?php

namespace App\Models;

use App\Models\Concerns\AuditsModelChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class CementReferenceValue extends Model
{
    use AuditsModelChanges;

    public const TYPE_SNI = 'sni';

    public const TYPE_KOMODITI = 'komoditi';

    public const TYPE_LSPRO = 'lspro';

    public const TYPE_JENIS_SERTIFIKASI = 'jenis_sertifikasi';

    public const TYPE_KEMASAN = 'kemasan';

    public const TYPE_PERINGKAT_GREEN_LABEL = 'peringkat_green_label';

    protected $fillable = [
        'type',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_SNI => 'SNI Semen',
            self::TYPE_KOMODITI => 'Komoditi / Jenis Semen',
            self::TYPE_LSPRO => 'LSPro',
            self::TYPE_JENIS_SERTIFIKASI => 'Jenis Sertifikasi',
            self::TYPE_KEMASAN => 'Kemasan',
            self::TYPE_PERINGKAT_GREEN_LABEL => 'Peringkat Green Label',
        ];
    }

    public static function labelFor(string $type): string
    {
        return self::typeLabels()[$type] ?? 'Referensi Semen';
    }

    public static function isValidType(string $type): bool
    {
        return array_key_exists($type, self::typeLabels());
    }

    public static function activeNameRule(string $type): mixed
    {
        return Rule::exists('cement_reference_values', 'name')
            ->where('type', $type)
            ->where('is_active', true);
    }
}
