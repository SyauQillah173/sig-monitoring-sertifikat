<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Cement\Concerns\ResolvesCementMasterPayload;
use App\Http\Controllers\Controller;
use App\Models\CementReferenceValue;
use App\Models\LokasiPabrik;
use App\Models\MerekSemen;
use App\Models\SertifikatTkdn;
use App\Services\CertificateFileStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SertifikatTkdnController extends Controller
{
    use ResolvesCementMasterPayload;

    public function __construct(
        private readonly CertificateFileStorage $files,
    ) {}

    public function index(Request $request): View
    {
        return view('cement.maintenance.certificates.tkdn.index', [
            'certificates' => SertifikatTkdn::query()
                ->with(['merekSemen.kategoriSemen', 'sniReference', 'komoditiReference', 'kemasanReference'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = '%'.$request->string('search').'%';
                    $query->where(fn ($subQuery) => $subQuery
                        ->where('sni', 'like', $search)
                        ->orWhere('komoditi', 'like', $search)
                        ->orWhere('kemasan', 'like', $search)
                        ->orWhereHas('merekSemen', fn ($brandQuery) => $brandQuery->where('nama_merek', 'like', $search)));
                })
                ->orderBy('berlaku_sd')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('cement.maintenance.certificates.tkdn.create', [
            'certificate' => new SertifikatTkdn,
            'brands' => $this->brandOptions(),
            'locations' => $this->locationOptions(),
            'references' => $this->referenceOptions([
                CementReferenceValue::TYPE_SNI,
                CementReferenceValue::TYPE_KOMODITI,
                CementReferenceValue::TYPE_KEMASAN,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validatedPayload($request);
        $payload['file_sertifikat'] = $this->files->store($request->file('file_sertifikat'), 'uploads/sertifikat');
        $certificate = SertifikatTkdn::query()->create($payload);

        return redirect()->route('cement.maintenance.sertifikat-tkdn.show', $certificate)->with('success', 'Sertifikat TKDN berhasil ditambahkan.');
    }

    public function show(SertifikatTkdn $sertifikatTkdn): View
    {
        return view('cement.maintenance.certificates.tkdn.show', [
            'certificate' => $sertifikatTkdn->load(['merekSemen.kategoriSemen', 'sniReference', 'komoditiReference', 'kemasanReference', 'lokasiPabrik']),
        ]);
    }

    public function edit(SertifikatTkdn $sertifikatTkdn): View
    {
        return view('cement.maintenance.certificates.tkdn.edit', [
            'certificate' => $sertifikatTkdn->load(['merekSemen.kategoriSemen', 'sniReference', 'komoditiReference', 'kemasanReference', 'lokasiPabrik']),
            'brands' => $this->brandOptions(),
            'locations' => $this->locationOptions($sertifikatTkdn->lokasi),
            'references' => $this->referenceOptions([
                CementReferenceValue::TYPE_SNI,
                CementReferenceValue::TYPE_KOMODITI,
                CementReferenceValue::TYPE_KEMASAN,
            ]),
        ]);
    }

    public function update(Request $request, SertifikatTkdn $sertifikatTkdn): RedirectResponse
    {
        $payload = $this->validatedPayload($request);

        if ($request->hasFile('file_sertifikat')) {
            if ($sertifikatTkdn->file_sertifikat) {
                $this->deleteCertificateFile($sertifikatTkdn->file_sertifikat);
            }

            $payload['file_sertifikat'] = $this->files->store($request->file('file_sertifikat'), 'uploads/sertifikat');
        }

        $sertifikatTkdn->update($payload);

        return redirect()->route('cement.maintenance.sertifikat-tkdn.show', $sertifikatTkdn)->with('success', 'Sertifikat TKDN berhasil diperbarui.');
    }

    public function destroy(SertifikatTkdn $sertifikatTkdn): RedirectResponse
    {
        if ($sertifikatTkdn->file_sertifikat) {
            $this->deleteCertificateFile($sertifikatTkdn->file_sertifikat);
        }

        $sertifikatTkdn->delete();

        return redirect()->route('cement.maintenance.sertifikat-tkdn.index')->with('success', 'Sertifikat TKDN berhasil dihapus.');
    }

    private function validatedPayload(Request $request): array
    {
        $request->validate([
            'merek_semen_id' => ['required', 'integer', Rule::exists('merek_semen', 'id')],
            ...$this->referenceSelectionRules(CementReferenceValue::TYPE_SNI, 'sni_reference_id', 'sni'),
            ...$this->referenceSelectionRules(CementReferenceValue::TYPE_KOMODITI, 'komoditi_reference_id', 'komoditi'),
            'persentase_tkdn' => ['required', 'numeric', 'min:0', 'max:100'],
            ...$this->referenceSelectionRules(CementReferenceValue::TYPE_KEMASAN, 'kemasan_reference_id', 'kemasan'),
            ...$this->locationSelectionRules(),
            'berlaku_sd' => ['required', 'date'],
            'file_sertifikat' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$this->files->maxUploadKilobytes()],
        ]);

        return [
            'merek_semen_id' => $request->integer('merek_semen_id'),
            ...$this->referencePayload($request, CementReferenceValue::TYPE_SNI, 'sni_reference_id', 'sni'),
            ...$this->referencePayload($request, CementReferenceValue::TYPE_KOMODITI, 'komoditi_reference_id', 'komoditi'),
            'persentase_tkdn' => $request->input('persentase_tkdn'),
            ...$this->referencePayload($request, CementReferenceValue::TYPE_KEMASAN, 'kemasan_reference_id', 'kemasan'),
            ...$this->locationPayload($request),
            'berlaku_sd' => $request->date('berlaku_sd')->format('Y-m-d'),
        ];
    }

    private function brandOptions()
    {
        return MerekSemen::query()->with('kategoriSemen')->orderBy('nama_merek')->get();
    }

    private function locationOptions(?string $currentLocation = null)
    {
        return LokasiPabrik::query()
            ->where('is_active', true)
            ->when($currentLocation, fn ($query) => $query->orWhere('nama_lokasi', $currentLocation))
            ->orderBy('nama_lokasi')
            ->get();
    }

    private function referenceOptions(array $types): array
    {
        return CementReferenceValue::query()
            ->whereIn('type', $types)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->groupBy('type')
            ->all();
    }

    private function deleteCertificateFile(string $path): void
    {
        $this->files->delete($path);
    }
}
