<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CertificateTypeController;
use App\Http\Controllers\Admin\IssuerController;
use App\Http\Controllers\Admin\System\NavigationItemController;
use App\Http\Controllers\Admin\System\PublicAppearanceController;
use App\Http\Controllers\Admin\System\SystemBackupController;
use App\Http\Controllers\Admin\System\SystemSettingsDashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\PasswordResetCodeController;
use App\Http\Controllers\Cement\CementCertificateDocumentController;
use App\Http\Controllers\Cement\CementCertificateDownloadController;
use App\Http\Controllers\Cement\CementCertificateProductController;
use App\Http\Controllers\Cement\CementExportController;
use App\Http\Controllers\Cement\CementImportController;
use App\Http\Controllers\Cement\CementReferenceController;
use App\Http\Controllers\Cement\CementSystemController;
use App\Http\Controllers\Cement\CertificateTemplateController;
use App\Http\Controllers\Cement\IsoStandardController;
use App\Http\Controllers\Cement\KategoriSemenController;
use App\Http\Controllers\Cement\KontakPerusahaanController;
use App\Http\Controllers\Cement\LokasiPabrikController;
use App\Http\Controllers\Cement\MaintenanceDashboardController;
use App\Http\Controllers\Cement\MerekSemenController;
use App\Http\Controllers\Cement\NotificationSettingController;
use App\Http\Controllers\Cement\PerusahaanSemenController;
use App\Http\Controllers\Cement\SertifikatGreenLabelController;
use App\Http\Controllers\Cement\SertifikatSistemSemenController;
use App\Http\Controllers\Cement\SertifikatSniController;
use App\Http\Controllers\Cement\SertifikatTkdnController;
use App\Http\Controllers\Cement\SystemAuditEvidenceDownloadController;
use App\Http\Controllers\Cement\SystemCertificateFollowUpController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Report\CertificateMonitoringReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingPageController::class, 'index'])->name('home');
Route::get('/landing-summary', [LandingPageController::class, 'summary'])
    ->middleware('throttle:60,1')
    ->name('home.summary');

Route::middleware('guest')->group(function () {
    Route::get('forgot-password', [PasswordResetCodeController::class, 'request'])->name('password.request');
    Route::post('forgot-password', [PasswordResetCodeController::class, 'send'])->name('password.email');
    Route::get('reset-password', [PasswordResetCodeController::class, 'code'])->name('password.code');
    Route::post('reset-password/verify-code', [PasswordResetCodeController::class, 'verify'])->name('password.code.verify');
    Route::get('reset-password/new', [PasswordResetCodeController::class, 'reset'])->name('password.reset');
    Route::post('reset-password', [PasswordResetCodeController::class, 'update'])->name('password.update');
});

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('sertifikat-sistem', [CementSystemController::class, 'index'])->name('cement.system.index');
    Route::get('sertifikat-produk', [CementCertificateProductController::class, 'index'])->name('cement.products.index');

    Route::get('sertifikat-semen/{type}/{certificate}/download', CementCertificateDownloadController::class)
        ->whereIn('type', ['sni', 'tkdn', 'green-label', 'system'])
        ->middleware(['app.role:admin,petugas', 'throttle:sensitive-download'])
        ->name('cement.certificates.download');

    Route::get('sertifikat-semen/{type}/{certificate}/dokumen', CementCertificateDocumentController::class)
        ->whereIn('type', ['sni', 'tkdn', 'green-label', 'system'])
        ->middleware(['app.role:admin,petugas', 'throttle:sensitive-download'])
        ->name('cement.certificates.document');

    Route::get('sertifikat-sistem-audit/{auditEvent}/download', SystemAuditEvidenceDownloadController::class)
        ->middleware(['app.role:admin,petugas', 'throttle:sensitive-download'])
        ->name('cement.system-audit-evidence.download');

    Route::prefix('sertifikat-sistem/{certificate}/tindak-lanjut/{action}')
        ->name('cement.system-follow-up.')
        ->whereIn('action', ['surveilen_1', 'surveilen_2', 'renewal'])
        ->middleware(['app.role:admin,petugas', 'throttle:sensitive-import'])
        ->group(function () {
            Route::get('/', [SystemCertificateFollowUpController::class, 'confirm'])->name('confirm');
            Route::post('/', [SystemCertificateFollowUpController::class, 'store'])->name('store');
        });

    Route::prefix('export-sertifikat-semen')->name('cement.exports.')->middleware(['app.role:admin', 'throttle:sensitive-export'])->group(function () {
        Route::get('/', [CementExportController::class, 'index'])->name('index');
        Route::get('template', [CementExportController::class, 'template'])->name('template');
        Route::get('sni', [CementExportController::class, 'sni'])->name('sni');
        Route::get('tkdn', [CementExportController::class, 'tkdn'])->name('tkdn');
        Route::get('green-label', [CementExportController::class, 'greenLabel'])->name('green-label');
        Route::get('semua', [CementExportController::class, 'all'])->name('all');
        Route::get('pdf', [CementExportController::class, 'pdf'])->name('pdf');
    });

    Route::prefix('pemeliharaan-data')->name('cement.maintenance.')->middleware('app.role:admin')->group(function () {
        Route::get('/', [MaintenanceDashboardController::class, 'index'])->name('index');
        Route::resource('kategori-semen', KategoriSemenController::class)
            ->except('show')
            ->parameters(['kategori-semen' => 'kategoriSemen']);
        Route::resource('merek-semen', MerekSemenController::class)
            ->except('show')
            ->parameters(['merek-semen' => 'merekSemen']);
        Route::resource('lokasi-pabrik', LokasiPabrikController::class)
            ->except('show')
            ->parameters(['lokasi-pabrik' => 'lokasiPabrik']);
        Route::resource('iso-standards', IsoStandardController::class)
            ->except('show')
            ->parameters(['iso-standards' => 'isoStandard']);
        Route::get('referensi/{type}', [CementReferenceController::class, 'index'])->name('references.index');
        Route::get('referensi/{type}/create', [CementReferenceController::class, 'create'])->name('references.create');
        Route::post('referensi/{type}', [CementReferenceController::class, 'store'])->name('references.store');
        Route::get('referensi/{type}/{reference}/edit', [CementReferenceController::class, 'edit'])->name('references.edit');
        Route::put('referensi/{type}/{reference}', [CementReferenceController::class, 'update'])->name('references.update');
        Route::delete('referensi/{type}/{reference}', [CementReferenceController::class, 'destroy'])->name('references.destroy');
        Route::resource('perusahaan-semen', PerusahaanSemenController::class)
            ->except('show')
            ->parameters(['perusahaan-semen' => 'perusahaanSemen']);
        Route::resource('kontak-perusahaan', KontakPerusahaanController::class)
            ->except('show')
            ->parameters(['kontak-perusahaan' => 'kontakPerusahaan']);
        Route::get('pengaturan-email-notifikasi', [NotificationSettingController::class, 'edit'])->name('notification-settings.edit');
        Route::put('pengaturan-email-notifikasi', [NotificationSettingController::class, 'update'])->name('notification-settings.update');
        Route::post('pengaturan-email-notifikasi/test', [NotificationSettingController::class, 'test'])->name('notification-settings.test');
        Route::get('template-sertifikat', [CertificateTemplateController::class, 'edit'])->name('certificate-template.edit');
        Route::put('template-sertifikat', [CertificateTemplateController::class, 'update'])->name('certificate-template.update');
        Route::delete('template-sertifikat', [CertificateTemplateController::class, 'reset'])->name('certificate-template.reset');
        Route::resource('sertifikat-sni', SertifikatSniController::class)
            ->parameters(['sertifikat-sni' => 'sertifikatSni']);
        Route::resource('sertifikat-tkdn', SertifikatTkdnController::class)
            ->parameters(['sertifikat-tkdn' => 'sertifikatTkdn']);
        Route::resource('sertifikat-green-label', SertifikatGreenLabelController::class)
            ->parameters(['sertifikat-green-label' => 'sertifikatGreenLabel']);
        Route::resource('sertifikat-sistem', SertifikatSistemSemenController::class)
            ->parameters(['sertifikat-sistem' => 'sertifikatSistem']);
    });

    Route::prefix('import-sertifikat-semen')->name('cement.import.')->middleware(['app.role:admin', 'throttle:sensitive-import'])->group(function () {
        Route::get('/', [CementImportController::class, 'index'])->name('index');
        Route::post('preview', [CementImportController::class, 'preview'])->name('preview');
        Route::post('simpan', [CementImportController::class, 'store'])->name('store');
    });

    Route::prefix('admin')->name('admin.')->middleware('app.role:admin')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::post('users/{user}/send-reset-link', [UserManagementController::class, 'sendResetLink'])->name('users.send-reset-link');
        Route::resource('users', UserManagementController::class)->except('show');

        Route::prefix('master-data')->group(function () {
            Route::resource('categories', CategoryController::class)->except('show');
            Route::resource('certificate-types', CertificateTypeController::class)
                ->except('show')
                ->parameters(['certificate-types' => 'certificateType']);
            Route::resource('issuers', IssuerController::class)->except('show');
        });
    });

    Route::prefix('pengaturan-sistem')->name('system-settings.')->middleware('app.role:admin')->group(function () {
        Route::get('/', SystemSettingsDashboardController::class)->name('index');
        Route::get('tampilan-publik', [PublicAppearanceController::class, 'edit'])->name('public-appearance.edit');
        Route::put('tampilan-publik', [PublicAppearanceController::class, 'update'])->name('public-appearance.update');
        Route::get('menu-aplikasi', [NavigationItemController::class, 'index'])->name('navigation.index');
        Route::put('menu-aplikasi', [NavigationItemController::class, 'update'])->name('navigation.update');
        Route::get('backup-maintenance', [SystemBackupController::class, 'index'])->name('backups.index');
        Route::put('backup-maintenance', [SystemBackupController::class, 'update'])->name('backups.update');
        Route::post('backup-maintenance', [SystemBackupController::class, 'store'])->middleware('throttle:sensitive-import')->name('backups.store');
        Route::post('backup-maintenance/cleanup', [SystemBackupController::class, 'cleanup'])->middleware('throttle:sensitive-import')->name('backups.cleanup');
        Route::get('backup-maintenance/{backup}/download', [SystemBackupController::class, 'download'])->middleware('throttle:sensitive-download')->name('backups.download');
        Route::delete('backup-maintenance/{backup}', [SystemBackupController::class, 'destroy'])->name('backups.destroy');
    });

    Route::prefix('petugas')->name('petugas.')->middleware('app.role:petugas')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'petugas'])->name('dashboard');
    });

    Route::middleware('app.role:admin,petugas')->group(function () {
        Route::get('certificates/{certificate}/download', [CertificateController::class, 'download'])
            ->middleware('throttle:sensitive-download')
            ->name('certificates.download');
        Route::resource('certificates', CertificateController::class);
    });

    Route::prefix('reports')->name('reports.')->middleware('app.role:admin')->group(function () {
        Route::get('certificates', [CertificateMonitoringReportController::class, 'index'])->name('certificates.index');
        Route::get('certificates/export/pdf', [CertificateMonitoringReportController::class, 'exportPdf'])
            ->middleware('throttle:sensitive-export')
            ->name('certificates.export-pdf');
        Route::get('certificates/export/excel', [CertificateMonitoringReportController::class, 'exportExcel'])
            ->middleware('throttle:sensitive-export')
            ->name('certificates.export-excel');
    });

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
});

require __DIR__.'/settings.php';
