<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Models\IsoStandard;
use App\Models\SertifikatSistemSemen;
use Illuminate\Contracts\View\View;

class CementSystemController extends Controller
{
    public function index(): View
    {
        $standards = IsoStandard::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        $certificates = SertifikatSistemSemen::query()
            ->with(['isoStandard', 'lokasiPabrik', 'auditEvents'])
            ->join('iso_standards', 'iso_standards.id', '=', 'sertifikat_sistem_semen.iso_standard_id')
            ->join('lokasi_pabrik', 'lokasi_pabrik.id', '=', 'sertifikat_sistem_semen.lokasi_pabrik_id')
            ->orderBy('iso_standards.sort_order')
            ->orderBy('iso_standards.code')
            ->orderBy('lokasi_pabrik.nama_lokasi')
            ->orderByDesc('sertifikat_sistem_semen.berlaku_sd')
            ->select('sertifikat_sistem_semen.*')
            ->get();

        return view('cement.system', [
            'standards' => $standards,
            'certificates' => $certificates,
            'totalCertificates' => $certificates->count(),
            'statusSummary' => [
                'aktif' => SertifikatSistemSemen::query()->filterExpiryStatus('aktif')->count(),
                'akan_berakhir' => SertifikatSistemSemen::query()->filterExpiryStatus('akan_berakhir')->count(),
                'kadaluarsa' => SertifikatSistemSemen::query()->filterExpiryStatus('kadaluarsa')->count(),
            ],
        ]);
    }
}
