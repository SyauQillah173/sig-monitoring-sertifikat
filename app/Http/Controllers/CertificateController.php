<?php

namespace App\Http\Controllers;

use App\Http\Requests\Certificate\StoreCertificateRequest;
use App\Http\Requests\Certificate\UpdateCertificateRequest;
use App\Models\Certificate;
use App\Services\Certificates\CertificateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\AuditLogger;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CertificateController extends Controller
{
    public function __construct(
        private readonly CertificateService $certificateService,
    ) {}

    public function index(Request $request): View
    {
        $selectedStatus = $request->string('status')->toString();

        return view('certificates.index', [
            'certificates' => $this->certificateService->paginateForIndex($selectedStatus),
            'selectedStatus' => array_key_exists($selectedStatus, Certificate::monitoringFilterOptions())
                ? $selectedStatus
                : 'all',
            'statusFilters' => Certificate::monitoringFilterOptions(),
        ]);
    }

    public function create(): View
    {
        return view('certificates.create', [
            'certificate' => new Certificate,
            ...$this->certificateService->formOptions(),
        ]);
    }

    public function store(StoreCertificateRequest $request): RedirectResponse
    {
        try {
            $certificate = $this->certificateService->create(
                $request->validated(),
                $request->user(),
                $request->file('document'),
            );

            return redirect()
                ->route('certificates.show', $certificate)
                ->with('success', 'Sertifikat berhasil ditambahkan.');
        } catch (Throwable $throwable) {
            report($throwable);

            return back()
                ->withInput()
                ->with('error', 'Sertifikat gagal disimpan. Silakan coba lagi.');
        }
    }

    public function show(Certificate $certificate): View
    {
        $certificate->load(['product.category', 'certificateType', 'issuer', 'issuedBy', 'updatedBy']);

        return view('certificates.show', [
            'certificate' => $certificate,
        ]);
    }

    public function edit(Certificate $certificate): View
    {
        $certificate->load(['product.category', 'certificateType', 'issuer']);

        return view('certificates.edit', [
            'certificate' => $certificate,
            ...$this->certificateService->formOptions(),
        ]);
    }

    public function update(UpdateCertificateRequest $request, Certificate $certificate): RedirectResponse
    {
        try {
            $certificate = $this->certificateService->update(
                $certificate,
                $request->validated(),
                $request->user(),
                $request->file('document'),
            );

            return redirect()
                ->route('certificates.show', $certificate)
                ->with('success', 'Sertifikat berhasil diperbarui.');
        } catch (Throwable $throwable) {
            report($throwable);

            return back()
                ->withInput()
                ->with('error', 'Sertifikat gagal diperbarui. Silakan coba lagi.');
        }
    }

    public function destroy(Certificate $certificate): RedirectResponse
    {
        try {
            $this->certificateService->delete($certificate);

            return redirect()
                ->route('certificates.index')
                ->with('success', 'Sertifikat berhasil dihapus.');
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('error', 'Sertifikat tidak dapat dihapus saat ini.');
        }
    }

    public function download(Certificate $certificate): StreamedResponse|RedirectResponse
    {
        if (! $this->certificateService->documentExists($certificate)) {
            return back()->with('error', 'Dokumen sertifikat tidak ditemukan.');
        }

        $disk = Storage::disk('local')->exists($certificate->file_path) ? 'local' : 'public';

        app(AuditLogger::class)->log('certificate_downloaded', $certificate, 'Dokumen sertifikat sistem diunduh.', null, [
            'file_path' => $certificate->file_path,
            'disk' => $disk,
        ]);

        return Storage::disk($disk)->download(
            $certificate->file_path,
            $certificate->downloadFilename(),
        );
    }
}
