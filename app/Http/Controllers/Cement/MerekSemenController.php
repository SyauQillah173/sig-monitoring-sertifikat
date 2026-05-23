<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Models\KategoriSemen;
use App\Models\MerekSemen;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class MerekSemenController extends Controller
{
    public function index(Request $request): View
    {
        return view('cement.maintenance.brands.index', [
            'brands' => MerekSemen::query()
                ->with('kategoriSemen')
                ->when($request->filled('search'), fn ($query) => $query->where('nama_merek', 'like', '%'.$request->string('search').'%'))
                ->when($request->integer('kategori_semen_id'), fn ($query, int $categoryId) => $query->where('kategori_semen_id', $categoryId))
                ->orderBy('nama_merek')
                ->paginate(10)
                ->withQueryString(),
            'categories' => KategoriSemen::query()->orderBy('nama_kategori')->get(),
        ]);
    }

    public function create(): View
    {
        return view('cement.maintenance.brands.create', [
            'brand' => new MerekSemen,
            'categories' => KategoriSemen::query()->orderBy('nama_kategori')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        MerekSemen::query()->create($this->validatedPayload($request));

        return redirect()->route('cement.maintenance.merek-semen.index')->with('success', 'Merek semen berhasil ditambahkan.');
    }

    public function edit(MerekSemen $merekSemen): View
    {
        return view('cement.maintenance.brands.edit', [
            'brand' => $merekSemen,
            'categories' => KategoriSemen::query()->orderBy('nama_kategori')->get(),
        ]);
    }

    public function update(Request $request, MerekSemen $merekSemen): RedirectResponse
    {
        $merekSemen->update($this->validatedPayload($request, $merekSemen));

        return redirect()->route('cement.maintenance.merek-semen.index')->with('success', 'Merek semen berhasil diperbarui.');
    }

    public function destroy(MerekSemen $merekSemen): RedirectResponse
    {
        try {
            $merekSemen->delete();

            return redirect()->route('cement.maintenance.merek-semen.index')->with('success', 'Merek semen berhasil dihapus.');
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('error', 'Merek semen tidak bisa dihapus karena masih memiliki sertifikat.');
        }
    }

    private function validatedPayload(Request $request, ?MerekSemen $brand = null): array
    {
        return $request->validate([
            'kategori_semen_id' => ['required', 'integer', Rule::exists('kategori_semen', 'id')],
            'nama_merek' => [
                'required',
                'string',
                'max:255',
                Rule::unique('merek_semen', 'nama_merek')
                    ->where('kategori_semen_id', $request->input('kategori_semen_id'))
                    ->ignore($brand?->id),
            ],
        ]);
    }
}
