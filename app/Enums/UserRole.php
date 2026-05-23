<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Petugas = 'petugas';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Petugas => 'Petugas',
        };
    }

    public function summary(): string
    {
        return match ($this) {
            self::Admin => 'Mengelola pengguna, hak akses, dan konfigurasi dasar sistem.',
            self::Petugas => 'Menginput, memperbarui, dan memantau data sertifikat produk.',
        };
    }

    public function dashboardRouteName(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Petugas => 'petugas.dashboard',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
