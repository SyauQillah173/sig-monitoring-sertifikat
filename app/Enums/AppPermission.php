<?php

namespace App\Enums;

enum AppPermission: string
{
    case DashboardView = 'dashboard.view';
    case UsersManage = 'users.manage';
    case MasterDataView = 'master-data.view';
    case MasterDataManage = 'master-data.manage';
    case CertificatesView = 'certificates.view';
    case CertificatesManage = 'certificates.manage';
    case ReportsView = 'reports.view';

    public function label(): string
    {
        return match ($this) {
            self::DashboardView => 'Akses dashboard',
            self::UsersManage => 'Kelola pengguna dan role',
            self::MasterDataView => 'Lihat master data',
            self::MasterDataManage => 'Kelola master data',
            self::CertificatesView => 'Lihat data sertifikat',
            self::CertificatesManage => 'Kelola data sertifikat',
            self::ReportsView => 'Lihat laporan',
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
