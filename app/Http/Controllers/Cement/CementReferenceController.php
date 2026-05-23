<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Models\CementReferenceValue;
use App\Models\SertifikatGreenLabel;
use App\Models\SertifikatSni;
use App\Models\SertifikatTkdn;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CementReferenceController extends Controller
{
    public function index(Request $request, string $type): View
    {
        $this->abortInvalidType($type);

        return view('cement.maintenance.references.index', [
            'type' => $type,
            'title' => CementReferenceValue::labelFor($type),
            'references' => CementReferenceValue::query()
                ->where('type', $type)
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = '%'.$request->string('search').'%';

                    $query->where(fn ($subQuery) => $subQuery
                        ->where('name', 'like', $search)
                        ->orWhere('code', 'like', $search)
                        ->orWhere('description', 'like', $search));
                })
                ->orderBy('name')
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function create(string $type): View
    {
        $this->abortInvalidType($type);

        return view('cement.maintenance.references.create', [
            'type' => $type,
            'title' => CementReferenceValue::labelFor($type),
            'reference' => new CementReferenceValue(['type' => $type, 'is_active' => true]),
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $this->abortInvalidType($type);

        CementReferenceValue::query()->create([
            'type' => $type,
            ...$this->validatedPayload($request, $type),
        ]);

        return redirect()
            ->route('cement.maintenance.references.index', $type)
            ->with('success', CementReferenceValue::labelFor($type).' berhasil ditambahkan.');
    }

    public function edit(string $type, CementReferenceValue $reference): View
    {
        $this->abortInvalidType($type);
        abort_unless($reference->type === $type, 404);

        return view('cement.maintenance.references.edit', [
            'type' => $type,
            'title' => CementReferenceValue::labelFor($type),
            'reference' => $reference,
        ]);
    }

    public function update(Request $request, string $type, CementReferenceValue $reference): RedirectResponse
    {
        $this->abortInvalidType($type);
        abort_unless($reference->type === $type, 404);

        $reference->update($this->validatedPayload($request, $type, $reference));

        return redirect()
            ->route('cement.maintenance.references.index', $type)
            ->with('success', CementReferenceValue::labelFor($type).' berhasil diperbarui.');
    }

    public function destroy(string $type, CementReferenceValue $reference): RedirectResponse
    {
        $this->abortInvalidType($type);
        abort_unless($reference->type === $type, 404);

        if ($this->isReferenceUsed($reference)) {
            return back()->with('error', 'Referensi ini masih dipakai pada data sertifikat, jadi tidak bisa dihapus.');
        }

        $reference->delete();

        return redirect()
            ->route('cement.maintenance.references.index', $type)
            ->with('success', CementReferenceValue::labelFor($type).' berhasil dihapus.');
    }

    private function abortInvalidType(string $type): void
    {
        abort_unless(CementReferenceValue::isValidType($type), 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, string $type, ?CementReferenceValue $reference = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cement_reference_values', 'name')
                    ->where('type', $type)
                    ->ignore($reference?->id),
            ],
            'code' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function isReferenceUsed(CementReferenceValue $reference): bool
    {
        return match ($reference->type) {
            CementReferenceValue::TYPE_SNI => $this->existsInCertificates('sni', $reference->name)
                || $this->existsInCertificateIds('sni_reference_id', $reference->id),
            CementReferenceValue::TYPE_KOMODITI => $this->existsInCertificates('komoditi', $reference->name)
                || $this->existsInCertificateIds('komoditi_reference_id', $reference->id),
            CementReferenceValue::TYPE_LSPRO => SertifikatSni::query()
                ->where('lspro_reference_id', $reference->id)
                ->orWhere('lspro', $reference->name)
                ->exists(),
            CementReferenceValue::TYPE_JENIS_SERTIFIKASI => SertifikatSni::query()
                ->where('jenis_sertifikasi_reference_id', $reference->id)
                ->orWhere('jenis_sertifikasi', $reference->name)
                ->exists(),
            CementReferenceValue::TYPE_KEMASAN => SertifikatTkdn::query()
                ->where('kemasan_reference_id', $reference->id)
                ->orWhere('kemasan', $reference->name)
                ->exists(),
            CementReferenceValue::TYPE_PERINGKAT_GREEN_LABEL => SertifikatGreenLabel::query()
                ->where('peringkat_green_label_reference_id', $reference->id)
                ->orWhere('peringkat', $reference->name)
                ->exists(),
            default => false,
        };
    }

    private function existsInCertificates(string $column, string $value): bool
    {
        return SertifikatSni::query()->where($column, $value)->exists()
            || SertifikatTkdn::query()->where($column, $value)->exists()
            || SertifikatGreenLabel::query()->where($column, $value)->exists();
    }

    private function existsInCertificateIds(string $column, int $value): bool
    {
        return SertifikatSni::query()->where($column, $value)->exists()
            || SertifikatTkdn::query()->where($column, $value)->exists()
            || SertifikatGreenLabel::query()->where($column, $value)->exists();
    }
}
