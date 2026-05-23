<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Cement\Concerns\ResolvesCementMasterPayload;
use App\Http\Controllers\Controller;
use App\Models\CementReferenceValue;
use App\Models\LokasiPabrik;
use App\Models\MerekSemen;
use App\Models\SertifikatGreenLabel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SertifikatGreenLabelController extends Controller
{
    use ResolvesCementMasterPayload;

    public function index(Request $request): View
    {
        return view('cement.maintenance.certificates.green-label.index', [
            'certificates' => SertifikatGreenLabel::query()
                ->with(['merekSemen.kategoriSemen', 'sniReference', 'komoditiReference', 'peringkatGreenLabelReference'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = '%'.$request->string('search').'%';
                    $query->where(fn ($subQuery) => $subQuery
                        ->where('sni', 'like', $search)
                        ->orWhere('komoditi', 'like', $search)
                        ->orWhere('peringkat', 'like', $search)
                        ->orWhereHas('merekSemen', fn ($brandQuery) => $brandQuery->where('nama_merek', 'like', $search)));
                })
                ->orderBy('berlaku_sd')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('cement.maintenance.certificates.green-label.create', [
            'certificate' => new SertifikatGreenLabel,
            'brands' => $this->brandOptions(),
            'locations' => $this->locationOptions(),
            'references' => $this->referenceOptions([
                CementReferenceValue::TYPE_SNI,
                CementReferenceValue::TYPE_KOMODITI,
                CementReferenceValue::TYPE_PERINGKAT_GREEN_LABEL,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validatedPayload($request);
        $payload['file_sertifikat'] = $request->file('file_sertifikat')?->store('uploads/sertifikat', 'local');
        $certificate = SertifikatGreenLabel::query()->create($payload);

        return redirect()->route('cement.maintenance.sertifikat-green-label.show', $certificate)->with('success', 'Sertifikat Green Label berhasil ditambahkan.');
    }

    public function show(SertifikatGreenLabel $sertifikatGreenLabel): View
    {
        return view('cement.maintenance.certificates.green-label.show', [
            'certificate' => $sertifikatGreenLabel->load(['merekSemen.kategoriSemen', 'sniReference', 'komoditiReference', 'peringkatGreenLabelReference', 'lokasiPabrik']),
        ]);
    }

    public function edit(SertifikatGreenLabel $sertifikatGreenLabel): View
    {
        return view('cement.maintenance.certificates.green-label.edit', [
            'certificate' => $sertifikatGreenLabel->load(['merekSemen.kategoriSemen', 'sniReference', 'komoditiReference', 'peringkatGreenLabelReference', 'lokasiPabrik']),
            'brands' => $this->brandOptions(),
            'locations' => $this->locationOptions($sertifikatGreenLabel->lokasi),
            'references' => $this->referenceOptions([
                CementReferenceValue::TYPE_SNI,
                CementReferenceValue::TYPE_KOMODITI,
                CementReferenceValue::TYPE_PERINGKAT_GREEN_LABEL,
            ]),
        ]);
    }

    public function update(Request $request, SertifikatGreenLabel $sertifikatGreenLabel): RedirectResponse
    {
        $payload = $this->validatedPayload($request);

        if ($request->hasFile('file_sertifikat')) {
            if ($sertifikatGreenLabel->file_sertifikat) {
                $this->deleteCertificateFile($sertifikatGreenLabel->file_sertifikat);
            }

            $payload['file_sertifikat'] = $request->file('file_sertifikat')->store('uploads/sertifikat', 'local');
        }

        $sertifikatGreenLabel->update($payload);

        return redirect()->route('cement.maintenance.sertifikat-green-label.show', $sertifikatGreenLabel)->with('success', 'Sertifikat Green Label berhasil diperbarui.');
    }

    public function destroy(SertifikatGreenLabel $sertifikatGreenLabel): RedirectResponse
    {
        if ($sertifikatGreenLabel->file_sertifikat) {
            $this->deleteCertificateFile($sertifikatGreenLabel->file_sertifikat);
        }

        $sertifikatGreenLabel->delete();

        return redirect()->route('cement.maintenance.sertifikat-green-label.index')->with('success', 'Sertifikat Green Label berhasil dihapus.');
    }

    private function validatedPayload(Request $request): array
    {
        $request->validate([
            'merek_semen_id' => ['required', 'integer', Rule::exists('merek_semen', 'id')],
            ...$this->referenceSelectionRules(CementReferenceValue::TYPE_SNI, 'sni_reference_id', 'sni'),
            ...$this->referenceSelectionRules(CementReferenceValue::TYPE_KOMODITI, 'komoditi_reference_id', 'komoditi'),
            ...$this->referenceSelectionRules(CementReferenceValue::TYPE_PERINGKAT_GREEN_LABEL, 'peringkat_green_label_reference_id', 'peringkat'),
            ...$this->locationSelectionRules(),
            'berlaku_sd' => ['required', 'date'],
            'file_sertifikat' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        return [
            'merek_semen_id' => $request->integer('merek_semen_id'),
            ...$this->referencePayload($request, CementReferenceValue::TYPE_SNI, 'sni_reference_id', 'sni'),
            ...$this->referencePayload($request, CementReferenceValue::TYPE_KOMODITI, 'komoditi_reference_id', 'komoditi'),
            ...$this->referencePayload($request, CementReferenceValue::TYPE_PERINGKAT_GREEN_LABEL, 'peringkat_green_label_reference_id', 'peringkat'),
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
