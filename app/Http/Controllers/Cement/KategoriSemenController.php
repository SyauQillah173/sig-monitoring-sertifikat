<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Models\KategoriSemen;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class KategoriSemenController extends Controller
{
    public function index(Request $request): View
    {
        return view('cement.maintenance.categories.index', [
            'categories' => KategoriSemen::query()
                ->withCount('merekSemen')
                ->when($request->filled('search'), fn ($query) => $query->where('nama_kategori', 'like', '%'.$request->string('search').'%'))
                ->orderBy('nama_kategori')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('cement.maintenance.categories.create', [
            'category' => new KategoriSemen,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_kategori' => ['required', 'string', 'max:255', Rule::unique('kategori_semen', 'nama_kategori')],
        ]);

        KategoriSemen::query()->create($validated);

        return redirect()->route('cement.maintenance.kategori-semen.index')->with('success', 'Kategori semen berhasil ditambahkan.');
    }

    public function edit(KategoriSemen $kategoriSemen): View
    {
        return view('cement.maintenance.categories.edit', [
            'category' => $kategoriSemen,
        ]);
    }

    public function update(Request $request, KategoriSemen $kategoriSemen): RedirectResponse
    {
        $validated = $request->validate([
            'nama_kategori' => ['required', 'string', 'max:255', Rule::unique('kategori_semen', 'nama_kategori')->ignore($kategoriSemen->id)],
        ]);

        $kategoriSemen->update($validated);

        return redirect()->route('cement.maintenance.kategori-semen.index')->with('success', 'Kategori semen berhasil diperbarui.');
    }

    public function destroy(KategoriSemen $kategoriSemen): RedirectResponse
    {
        try {
            $kategoriSemen->delete();

            return redirect()->route('cement.maintenance.kategori-semen.index')->with('success', 'Kategori semen berhasil dihapus.');
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('error', 'Kategori semen tidak bisa dihapus karena masih memiliki merek.');
        }
    }
}
