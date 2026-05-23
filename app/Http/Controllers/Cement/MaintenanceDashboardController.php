<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Models\CementReferenceValue;
use App\Models\IsoStandard;
use App\Models\KategoriSemen;
use App\Models\KontakPerusahaan;
use App\Models\LokasiPabrik;
use App\Models\MerekSemen;
use App\Models\PerusahaanSemen;
use App\Models\SertifikatGreenLabel;
use App\Models\SertifikatSistemSemen;
use App\Models\SertifikatSni;
use App\Models\SertifikatTkdn;
use Illuminate\Contracts\View\View;

class MaintenanceDashboardController extends Controller
{
    public function index(): View
    {
        $referenceCounts = CementReferenceValue::query()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return view('cement.maintenance.index', [
            'summary' => [
                'kategori' => KategoriSemen::query()->count(),
                'merek' => MerekSemen::query()->count(),
                'lokasi' => LokasiPabrik::query()->count(),
                'referensi' => CementReferenceValue::query()->count(),
                'iso_standards' => IsoStandard::query()->count(),
                'perusahaan' => PerusahaanSemen::query()->count(),
                'kontak' => KontakPerusahaan::query()->count(),
                'sni' => SertifikatSni::query()->count(),
                'tkdn' => SertifikatTkdn::query()->count(),
                'green_label' => SertifikatGreenLabel::query()->count(),
                'system' => SertifikatSistemSemen::query()->count(),
            ],
            'referenceTypes' => CementReferenceValue::typeLabels(),
            'referenceCounts' => $referenceCounts,
        ]);
    }
}
