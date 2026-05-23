<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Cement\Concerns\ResolvesCementMasterPayload;
use App\Http\Controllers\Controller;
use App\Models\CementReferenceValue;
use App\Models\LokasiPabrik;
use App\Models\MerekSemen;
use App\Models\SertifikatSni;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SertifikatSniController extends Controller
{
    use ResolvesCementMasterPayload;

    public function index(Request $request): View
    {
        return view('cement.maintenance.certificates.sni.index', [
            'certificates' => SertifikatSni::query()
                ->with(['merekSemen.kategoriSemen', 'sniReference', 'komoditiReference', 'lsproReference'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = '%'.$request->string('search').'%';
                    $query->where(fn ($subQuery) => $subQuery
                        ->where('sni', 'like', $search)
                        ->orWhere('komoditi', 'like', $search)
                        ->orWhere('lspro', 'like', $search)
                        ->orWhereHas('merekSemen', fn ($brandQuery) => $brandQuery->where('nama_merek', 'like', $search)));
                })
                ->orderBy('berlaku_sd')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('cement.maintenance.certificates.sni.create', [
            'certificate' => new SertifikatSni,
            'brands' => $this->brandOptions(),
            'locations' => $this->locationOptions(),
            'references' => $this->referenceOptions([
                CementReferenceValue::TYPE_SNI,
                CementReferenceValue::TYPE_KOMODITI,
                CementReferenceValue::TYPE_JENIS_SERTIFIKASI,
                CementReferenceValue::TYPE_LSPRO,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validatedPayload($request);
        $payload['file_sertifikat'] = $request->file('file_sertifikat')?->store('uploads/sertifikat', 'local');

        $certificate = SertifikatSni::query()->create($payload);

        return redirect()->route('cement.maintenance.sertifikat-sni.show', $certificate)->with('success', 'Sertifikat SNI berhasil ditambahkan.');
    }

    public function show(SertifikatSni $sertifikatSni): View
    {
        return view('cement.maintenance.certificates.sni.show', [
            'certificate' => $sertifikatSni->load(['merekSemen.kategoriSemen', 'sniReference', 'komoditiReference', 'jenisSertifikasiReference', 'lsproReference', 'lokasiPabrik']),
        ]);
    }

    public function edit(SertifikatSni $sertifikatSni): View
    {
        return view('cement.maintenance.certificates.sni.edit', [
            'certificate' => $sertifikatSni->load(['merekSemen.kategoriSemen', 'sniReference', 'komoditiReference', 'jenisSertifikasiReference', 'lsproReference', 'lokasiPabrik']),
            'brands' => $this->brandOptions(),
            'locations' => $this->locationOptions($sertifikatSni->lokasi),
            'references' => $this->referenceOptions([
                CementReferenceValue::TYPE_SNI,
                CementReferenceValue::TYPE_KOMODITI,
                CementReferenceValue::TYPE_JENIS_SERTIFIKASI,
                CementReferenceValue::TYPE_LSPRO,
            ]),
        ]);
    }

    public function update(Request $request, SertifikatSni $sertifikatSni): RedirectResponse
    {
        $payload = $this->validatedPayload($request);

        if ($request->hasFile('file_sertifikat')) {
            if ($sertifikatSni->file_sertifikat) {
                $this->deleteCertificateFile($sertifikatSni->file_sertifikat);
            }

            $payload['file_sertifikat'] = $request->file('file_sertifikat')->store('uploads/sertifikat', 'local');
        }

        $sertifikatSni->update($payload);

        return redirect()->route('cement.maintenance.sertifikat-sni.show', $sertifikatSni)->with('success', 'Sertifikat SNI berhasil diperbarui.');
    }

    public function destroy(SertifikatSni $sertifikatSni): RedirectResponse
    {
        if ($sertifikatSni->file_sertifikat) {
            $this->deleteCertificateFile($sertifikatSni->file_sertifikat);
        }

        $sertifikatSni->delete();

        return redirect()->route('cement.maintenance.sertifikat-sni.index')->with('success', 'Sertifikat SNI berhasil dihapus.');
    }

    private function validatedPayload(Request $request): array
    {
        $request->validate([
            'merek_semen_id' => ['required', 'integer', Rule::exists('merek_semen', 'id')],
            ...$this->referenceSelectionRules(CementReferenceValue::TYPE_SNI, 'sni_reference_id', 'sni'),
            ...$this->referenceSelectionRules(CementReferenceValue::TYPE_KOMODITI, 'komoditi_reference_id', 'komoditi'),
            ...$this->referenceSelectionRules(CementReferenceValue::TYPE_JENIS_SERTIFIKASI, 'jenis_sertifikasi_reference_id', 'jenis_sertifikasi'),
            ...$this->referenceSelectionRules(CementReferenceValue::TYPE_LSPRO, 'lspro_reference_id', 'lspro'),
            ...$this->locationSelectionRules(),
            'berlaku_sd' => ['required', 'date'],
            'file_sertifikat' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        return [
            'merek_semen_id' => $request->integer('merek_semen_id'),
            ...$this->referencePayload($request, CementReferenceValue::TYPE_SNI, 'sni_reference_id', 'sni'),
            ...$this->referencePayload($request, CementReferenceValue::TYPE_KOMODITI, 'komoditi_reference_id', 'komoditi'),
            ...$this->referencePayload($request, CementReferenceValue::TYPE_JENIS_SERTIFIKASI, 'jenis_sertifikasi_reference_id', 'jenis_sertifikasi'),
            ...$this->referencePayload($request, CementReferenceValue::TYPE_LSPRO, 'lspro_reference_id', 'lspro'),
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
        Storage::disk('local')->delete($path);
        Storage::disk('public')->delete($path);
    }
}
