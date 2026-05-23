<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Models\PerusahaanSemen;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PerusahaanSemenController extends Controller
{
    public function index(Request $request): View
    {
        return view('cement.maintenance.companies.index', [
            'companies' => PerusahaanSemen::query()
                ->withCount('kontakPerusahaan')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = '%'.$request->string('search').'%';

                    $query->where(fn ($subQuery) => $subQuery
                        ->where('nama_perusahaan', 'like', $search)
                        ->orWhere('kode', 'like', $search)
                        ->orWhere('alamat', 'like', $search));
                })
                ->orderBy('nama_perusahaan')
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('cement.maintenance.companies.create', [
            'company' => new PerusahaanSemen(['is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        PerusahaanSemen::query()->create($this->validatedPayload($request));

        return redirect()->route('cement.maintenance.perusahaan-semen.index')->with('success', 'Perusahaan semen berhasil ditambahkan.');
    }

    public function edit(PerusahaanSemen $perusahaanSemen): View
    {
        return view('cement.maintenance.companies.edit', [
            'company' => $perusahaanSemen,
        ]);
    }

    public function update(Request $request, PerusahaanSemen $perusahaanSemen): RedirectResponse
    {
        $perusahaanSemen->update($this->validatedPayload($request, $perusahaanSemen));

        return redirect()->route('cement.maintenance.perusahaan-semen.index')->with('success', 'Perusahaan semen berhasil diperbarui.');
    }

    public function destroy(PerusahaanSemen $perusahaanSemen): RedirectResponse
    {
        $perusahaanSemen->delete();

        return redirect()->route('cement.maintenance.perusahaan-semen.index')->with('success', 'Perusahaan semen berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, ?PerusahaanSemen $company = null): array
    {
        return $request->validate([
            'nama_perusahaan' => ['required', 'string', 'max:255', Rule::unique('perusahaan_semen', 'nama_perusahaan')->ignore($company?->id)],
            'kode' => ['nullable', 'string', 'max:80', Rule::unique('perusahaan_semen', 'kode')->ignore($company?->id)],
            'alamat' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
