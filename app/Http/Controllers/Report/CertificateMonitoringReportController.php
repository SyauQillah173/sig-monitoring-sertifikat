<?php

namespace App\Http\Controllers\Report;

use App\Exports\CertificateMonitoringReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\CertificateMonitoringReportRequest;
use App\Services\AuditLogger;
use App\Services\Reports\CertificateMonitoringReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class CertificateMonitoringReportController extends Controller
{
    public function __construct(
        private readonly CertificateMonitoringReportService $reportService,
    ) {}

    public function index(CertificateMonitoringReportRequest $request): View
    {
        $filters = $request->filters();

        return view('reports.certificates.index', [
            'filters' => $filters,
            'report' => $this->reportService->paginate($filters),
            'summary' => $this->reportService->summary($filters),
            ...$this->reportService->filterOptions(),
        ]);
    }

    public function exportPdf(CertificateMonitoringReportRequest $request): Response
    {
        $filters = $request->filters();
        $certificates = $this->reportService->get($filters);
        $this->auditExport('pdf', $filters, $certificates->count());

        $pdf = Pdf::loadView('reports.certificates.pdf', [
            'certificates' => $certificates,
            'filters' => $filters,
            'summary' => $this->reportService->summary($filters),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-monitoring-sertifikat.pdf');
    }

    public function exportExcel(CertificateMonitoringReportRequest $request): BinaryFileResponse
    {
        $filters = $request->filters();
        $certificates = $this->reportService->get($filters);
        $this->auditExport('excel', $filters, $certificates->count());

        return Excel::download(
            new CertificateMonitoringReportExport($certificates),
            'laporan-monitoring-sertifikat.xlsx',
        );
    }

    private function auditExport(string $type, array $filters, int $rows): void
    {
        app(AuditLogger::class)->log('certificate_report_exported', null, 'Laporan sertifikat diexport.', null, [
            'export_type' => $type,
            'filters' => $filters,
            'rows' => $rows,
        ]);
    }
}
