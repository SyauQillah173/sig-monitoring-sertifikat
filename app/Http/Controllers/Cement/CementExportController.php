<?php

namespace App\Http\Controllers\Cement;

use App\Exports\Cement\CementRowsExport;
use App\Exports\Cement\CementTemplateWorkbookExport;
use App\Exports\Cement\CementWorkbookExport;
use App\Http\Controllers\Controller;
use App\Models\CementReferenceValue;
use App\Models\LokasiPabrik;
use App\Models\MerekSemen;
use App\Services\AuditLogger;
use App\Services\Cement\CementDashboardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class CementExportController extends Controller
{
    public function __construct(
        private readonly CementDashboardService $dashboardService,
    ) {}

    public function index(): View
    {
        return view('cement.exports.index');
    }

    public function template(): BinaryFileResponse
    {
        $this->auditExport('template');

        return Excel::download($this->templateExport(), 'template-import-sertifikat-semen.xlsx');
    }

    public function sni(Request $request): BinaryFileResponse
    {
        $data = $this->filteredData($request);
        $this->auditExport('sni', $request, ['rows' => $data['sertifikatSni']->count()]);

        return Excel::download($this->sniSheet($data['sertifikatSni']->all()), 'sertifikat-sni-semen.xlsx');
    }

    public function tkdn(Request $request): BinaryFileResponse
    {
        $data = $this->filteredData($request);
        $this->auditExport('tkdn', $request, ['rows' => $data['sertifikatTkdn']->count()]);

        return Excel::download($this->tkdnSheet($data['sertifikatTkdn']->all()), 'sertifikat-tkdn-semen.xlsx');
    }

    public function greenLabel(Request $request): BinaryFileResponse
    {
        $data = $this->filteredData($request);
        $this->auditExport('green-label', $request, ['rows' => $data['sertifikatGreenLabel']->count()]);

        return Excel::download($this->greenLabelSheet($data['sertifikatGreenLabel']->all()), 'sertifikat-green-label-semen.xlsx');
    }

    public function all(Request $request): BinaryFileResponse
    {
        $data = $this->filteredData($request);
        $this->auditExport('all', $request, [
            'sni_rows' => $data['sertifikatSni']->count(),
            'tkdn_rows' => $data['sertifikatTkdn']->count(),
            'green_label_rows' => $data['sertifikatGreenLabel']->count(),
        ]);

        return Excel::download(new CementWorkbookExport([
            $this->sniSheet($data['sertifikatSni']->all()),
            $this->tkdnSheet($data['sertifikatTkdn']->all()),
            $this->greenLabelSheet($data['sertifikatGreenLabel']->all()),
        ]), 'semua-sertifikat-semen.xlsx');
    }

    public function pdf(Request $request): Response
    {
        $data = $this->filteredData($request);
        $this->auditExport('pdf', $request, [
            'sni_rows' => $data['sertifikatSni']->count(),
            'tkdn_rows' => $data['sertifikatTkdn']->count(),
            'green_label_rows' => $data['sertifikatGreenLabel']->count(),
        ]);
        $pdf = Pdf::loadView('cement.exports.pdf', $data)->setPaper('a4', 'landscape');

        return $pdf->download('laporan-sertifikat-semen.pdf');
    }

    public function templateExport(): CementTemplateWorkbookExport
    {
        return new CementTemplateWorkbookExport(
            $this->templateBrands(),
            $this->templateLocations(),
            $this->templateReferences(),
        );
    }

    private function templateBrands(): array
    {
        return MerekSemen::query()
            ->with('kategoriSemen')
            ->orderBy('id')
            ->get()
            ->map(fn (MerekSemen $brand) => [
                $brand->id,
                $brand->kategori_semen_id,
                $brand->kategoriSemen?->nama_kategori,
                $brand->nama_merek,
            ])
            ->all();
    }

    private function templateLocations(): array
    {
        return LokasiPabrik::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn (LokasiPabrik $location) => [
                $location->id,
                $location->nama_lokasi,
                $location->kode,
                $location->is_active ? 'AKTIF' : 'NONAKTIF',
            ])
            ->all();
    }

    private function templateReferences(): array
    {
        return CementReferenceValue::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('id')
            ->get()
            ->groupBy('type')
            ->map(fn ($references) => $references
                ->map(fn (CementReferenceValue $reference) => [
                    $reference->id,
                    CementReferenceValue::labelFor($reference->type),
                    $reference->name,
                    $reference->code,
                ])
                ->all())
            ->all();
    }

    private function filteredData(Request $request): array
    {
        return $this->dashboardService->build($request->query());
    }

    private function auditExport(string $type, ?Request $request = null, array $context = []): void
    {
        app(AuditLogger::class)->log('cement_certificate_exported', null, 'Data sertifikat semen diexport.', null, [
            'export_type' => $type,
            'filters' => $request?->query() ?? [],
            ...$context,
        ]);
    }

    private function sniSheet(array $certificates): CementRowsExport
    {
        return new CementRowsExport('Sertifikat SNI', $this->sniHeadings(), collect($certificates)->map(fn ($certificate) => [
            'kategori' => $certificate->merekSemen?->kategoriSemen?->nama_kategori,
            'merek' => $certificate->merekSemen?->nama_merek,
            'sni' => $certificate->sni,
            'komoditi' => $certificate->komoditi,
            'jenis_sertifikasi' => $certificate->jenis_sertifikasi,
            'lspro' => $certificate->lspro,
            'lokasi' => $certificate->lokasi,
            'berlaku_sd' => $certificate->berlaku_sd?->format('Y-m-d'),
            'file_sertifikat' => $certificate->file_sertifikat,
            'status' => $certificate->statusLabel(),
        ])->all());
    }

    private function tkdnSheet(array $certificates): CementRowsExport
    {
        return new CementRowsExport('Sertifikat TKDN', $this->tkdnHeadings(), collect($certificates)->map(fn ($certificate) => [
            'kategori' => $certificate->merekSemen?->kategoriSemen?->nama_kategori,
            'merek' => $certificate->merekSemen?->nama_merek,
            'sni' => $certificate->sni,
            'komoditi' => $certificate->komoditi,
            'persentase_tkdn' => $certificate->persentase_tkdn,
            'kemasan' => $certificate->kemasan,
            'lokasi' => $certificate->lokasi,
            'berlaku_sd' => $certificate->berlaku_sd?->format('Y-m-d'),
            'file_sertifikat' => $certificate->file_sertifikat,
            'status' => $certificate->statusLabel(),
        ])->all());
    }

    private function greenLabelSheet(array $certificates): CementRowsExport
    {
        return new CementRowsExport('Sertifikat Green Label', $this->greenLabelHeadings(), collect($certificates)->map(fn ($certificate) => [
            'kategori' => $certificate->merekSemen?->kategoriSemen?->nama_kategori,
            'merek' => $certificate->merekSemen?->nama_merek,
            'sni' => $certificate->sni,
            'komoditi' => $certificate->komoditi,
            'peringkat' => $certificate->peringkat,
            'lokasi' => $certificate->lokasi,
            'berlaku_sd' => $certificate->berlaku_sd?->format('Y-m-d'),
            'file_sertifikat' => $certificate->file_sertifikat,
            'status' => $certificate->statusLabel(),
        ])->all());
    }

    private function sniHeadings(): array
    {
        return ['kategori', 'merek', 'sni', 'komoditi', 'jenis_sertifikasi', 'lspro', 'lokasi', 'berlaku_sd', 'file_sertifikat'];
    }

    private function tkdnHeadings(): array
    {
        return ['kategori', 'merek', 'sni', 'komoditi', 'persentase_tkdn', 'kemasan', 'lokasi', 'berlaku_sd', 'file_sertifikat'];
    }

    private function greenLabelHeadings(): array
    {
        return ['kategori', 'merek', 'sni', 'komoditi', 'peringkat', 'lokasi', 'berlaku_sd', 'file_sertifikat'];
    }
}
