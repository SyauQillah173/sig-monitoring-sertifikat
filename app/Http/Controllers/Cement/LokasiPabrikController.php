<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Models\LokasiPabrik;
use App\Models\SertifikatGreenLabel;
use App\Models\SertifikatSni;
use App\Models\SertifikatTkdn;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LokasiPabrikController extends Controller
{
    public function index(Request $request): View
    {
        return view('cement.maintenance.locations.index', [
            'locations' => LokasiPabrik::query()
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = '%'.$request->string('search').'%';

                    $query->where(fn ($subQuery) => $subQuery
                        ->where('nama_lokasi', 'like', $search)
                        ->orWhere('kode', 'like', $search)
                        ->orWhere('alamat', 'like', $search));
                })
                ->orderBy('nama_lokasi')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('cement.maintenance.locations.create', [
            'location' => new LokasiPabrik(['is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        LokasiPabrik::query()->create($this->validatedPayload($request));

        return redirect()->route('cement.maintenance.lokasi-pabrik.index')->with('success', 'Lokasi pabrik berhasil ditambahkan.');
    }

    public function edit(LokasiPabrik $lokasiPabrik): View
    {
        return view('cement.maintenance.locations.edit', [
            'location' => $lokasiPabrik,
        ]);
    }

    public function update(Request $request, LokasiPabrik $lokasiPabrik): RedirectResponse
    {
        $lokasiPabrik->update($this->validatedPayload($request, $lokasiPabrik));

        return redirect()->route('cement.maintenance.lokasi-pabrik.index')->with('success', 'Lokasi pabrik berhasil diperbarui.');
    }

    public function destroy(LokasiPabrik $lokasiPabrik): RedirectResponse
    {
        if ($this->isLocationUsed($lokasiPabrik)) {
            return back()->with('error', 'Lokasi pabrik ini masih dipakai pada data sertifikat, jadi tidak bisa dihapus.');
        }

        $lokasiPabrik->delete();

        return redirect()->route('cement.maintenance.lokasi-pabrik.index')->with('success', 'Lokasi pabrik berhasil dihapus.');
    }

    private function validatedPayload(Request $request, ?LokasiPabrik $location = null): array
    {
        return $request->validate([
            'nama_lokasi' => ['required', 'string', 'max:255', Rule::unique('lokasi_pabrik', 'nama_lokasi')->ignore($location?->id)],
            'kode' => ['nullable', 'string', 'max:50', Rule::unique('lokasi_pabrik', 'kode')->ignore($location?->id)],
            'alamat' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => false];
    }

    private function isLocationUsed(LokasiPabrik $location): bool
    {
        return SertifikatSni::query()
            ->where('lokasi_pabrik_id', $location->id)
            ->orWhere('lokasi', $location->nama_lokasi)
            ->exists()
            || SertifikatTkdn::query()
                ->where('lokasi_pabrik_id', $location->id)
                ->orWhere('lokasi', $location->nama_lokasi)
                ->exists()
            || SertifikatGreenLabel::query()
                ->where('lokasi_pabrik_id', $location->id)
                ->orWhere('lokasi', $location->nama_lokasi)
                ->exists();
    }
}
