<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Models\IsoStandard;
use App\Models\LokasiPabrik;
use App\Models\SertifikatSistemSemen;
use App\Services\SystemCertificateAuditTimelineService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SertifikatSistemSemenController extends Controller
{
    public function __construct(
        private readonly SystemCertificateAuditTimelineService $auditTimeline,
    ) {}

    public function index(Request $request): View
    {
        return view('cement.maintenance.system-certificates.index', [
            'certificates' => SertifikatSistemSemen::query()
                ->with(['isoStandard', 'lokasiPabrik'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = '%'.$request->string('search').'%';

                    $query->where(fn ($subQuery) => $subQuery
                        ->where('certificate_number', 'like', $search)
                        ->orWhere('issuer', 'like', $search)
                        ->orWhere('scope', 'like', $search)
                        ->orWhere('certification_category', 'like', $search)
                        ->orWhere('process_owner', 'like', $search)
                        ->orWhereHas('isoStandard', fn ($isoQuery) => $isoQuery
                            ->where('code', 'like', $search)
                            ->orWhere('name', 'like', $search))
                        ->orWhereHas('lokasiPabrik', fn ($locationQuery) => $locationQuery->where('nama_lokasi', 'like', $search)));
                })
                ->orderBy('berlaku_sd')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('cement.maintenance.system-certificates.create', [
            'certificate' => new SertifikatSistemSemen([
                'audit_stage' => SertifikatSistemSemen::AUDIT_STAGE_SURVEILEN_1,
                'scope' => 'Produksi semen',
                'acquisition_year' => now()->year,
                'certification_level' => SertifikatSistemSemen::LEVEL_INTERNASIONAL,
            ]),
            'standards' => $this->standardOptions(),
            'locations' => $this->locationOptions(),
            'auditStages' => SertifikatSistemSemen::auditStageOptions(),
            'certificationLevels' => SertifikatSistemSemen::certificationLevelOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validatedPayload($request);
        $payload['file_sertifikat'] = $request->file('file_sertifikat')?->store('uploads/sertifikat-sistem', 'local');

        $certificate = SertifikatSistemSemen::query()->create($payload);
        $this->auditTimeline->syncFor($certificate);

        return redirect()->route('cement.maintenance.sertifikat-sistem.show', $certificate)->with('success', 'Sertifikat sistem ISO berhasil ditambahkan.');
    }

    public function show(SertifikatSistemSemen $sertifikatSistem): View
    {
        return view('cement.maintenance.system-certificates.show', [
            'certificate' => $sertifikatSistem->load(['isoStandard', 'lokasiPabrik', 'auditEvents.user']),
        ]);
    }

    public function edit(SertifikatSistemSemen $sertifikatSistem): View
    {
        return view('cement.maintenance.system-certificates.edit', [
            'certificate' => $sertifikatSistem->load(['isoStandard', 'lokasiPabrik']),
            'standards' => $this->standardOptions($sertifikatSistem->iso_standard_id),
            'locations' => $this->locationOptions($sertifikatSistem->lokasi_pabrik_id),
            'auditStages' => SertifikatSistemSemen::auditStageOptions(),
            'certificationLevels' => SertifikatSistemSemen::certificationLevelOptions(),
        ]);
    }

    public function update(Request $request, SertifikatSistemSemen $sertifikatSistem): RedirectResponse
    {
        $payload = $this->validatedPayload($request, $sertifikatSistem);

        if ($request->hasFile('file_sertifikat')) {
            if ($sertifikatSistem->file_sertifikat) {
                $this->deleteCertificateFile($sertifikatSistem->file_sertifikat);
            }

            $payload['file_sertifikat'] = $request->file('file_sertifikat')->store('uploads/sertifikat-sistem', 'local');
        }

        $sertifikatSistem->update($payload);
        $this->auditTimeline->syncFor($sertifikatSistem->fresh());

        return redirect()->route('cement.maintenance.sertifikat-sistem.show', $sertifikatSistem)->with('success', 'Sertifikat sistem ISO berhasil diperbarui.');
    }

    public function destroy(SertifikatSistemSemen $sertifikatSistem): RedirectResponse
    {
        if ($sertifikatSistem->file_sertifikat) {
            $this->deleteCertificateFile($sertifikatSistem->file_sertifikat);
        }

        $sertifikatSistem->delete();

        return redirect()->route('cement.maintenance.sertifikat-sistem.index')->with('success', 'Sertifikat sistem ISO berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, ?SertifikatSistemSemen $certificate = null): array
    {
        $validated = $request->validate([
            'lokasi_pabrik_id' => ['required', 'integer', Rule::exists('lokasi_pabrik', 'id')->where('is_active', true)],
            'iso_standard_id' => ['required', 'integer', Rule::exists('iso_standards', 'id')->where('is_active', true)],
            'certificate_number' => ['required', 'string', 'max:255', Rule::unique('sertifikat_sistem_semen', 'certificate_number')->ignore($certificate?->id)],
            'issuer' => ['nullable', 'string', 'max:255'],
            'audit_stage' => ['required', Rule::in(array_keys(SertifikatSistemSemen::auditStageOptions()))],
            'scope' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['required', 'date'],
            'berlaku_sd' => ['required', 'date', 'after_or_equal:issued_at'],
            'acquisition_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'certification_level' => ['nullable', Rule::in(array_keys(SertifikatSistemSemen::certificationLevelOptions()))],
            'certification_category' => ['nullable', 'string', 'max:255'],
            'process_owner' => ['nullable', 'string', 'max:255'],
            'accreditation_number' => ['nullable', 'string', 'max:255'],
            'public_url' => ['nullable', 'url', 'max:2048'],
            'description' => ['nullable', 'string', 'max:4000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'file_sertifikat' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        unset($validated['file_sertifikat']);
        $validated['acquisition_year'] = ($validated['acquisition_year'] ?? null) ?: (int) date('Y', strtotime($validated['issued_at']));

        return $validated;
    }

    private function standardOptions(?int $currentStandardId = null)
    {
        return IsoStandard::query()
            ->where('is_active', true)
            ->when($currentStandardId, fn ($query) => $query->orWhere('id', $currentStandardId))
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }

    private function locationOptions(?int $currentLocationId = null)
    {
        return LokasiPabrik::query()
            ->where('is_active', true)
            ->when($currentLocationId, fn ($query) => $query->orWhere('id', $currentLocationId))
            ->orderBy('nama_lokasi')
            ->get();
    }

    private function deleteCertificateFile(string $path): void
    {
        Storage::disk('local')->delete($path);
        Storage::disk('public')->delete($path);
    }
}
