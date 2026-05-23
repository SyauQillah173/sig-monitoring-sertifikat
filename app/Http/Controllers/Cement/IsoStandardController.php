<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Models\IsoStandard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IsoStandardController extends Controller
{
    public function index(Request $request): View
    {
        return view('cement.maintenance.iso-standards.index', [
            'standards' => IsoStandard::query()
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = '%'.$request->string('search').'%';

                    $query->where(fn ($subQuery) => $subQuery
                        ->where('code', 'like', $search)
                        ->orWhere('name', 'like', $search)
                        ->orWhere('description', 'like', $search));
                })
                ->orderBy('sort_order')
                ->orderBy('code')
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('cement.maintenance.iso-standards.create', [
            'standard' => new IsoStandard(['is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        IsoStandard::query()->create($this->validatedPayload($request));

        return redirect()->route('cement.maintenance.iso-standards.index')->with('success', 'Master standar ISO berhasil ditambahkan.');
    }

    public function edit(IsoStandard $isoStandard): View
    {
        return view('cement.maintenance.iso-standards.edit', [
            'standard' => $isoStandard,
        ]);
    }

    public function update(Request $request, IsoStandard $isoStandard): RedirectResponse
    {
        $isoStandard->update($this->validatedPayload($request, $isoStandard));

        return redirect()->route('cement.maintenance.iso-standards.index')->with('success', 'Master standar ISO berhasil diperbarui.');
    }

    public function destroy(IsoStandard $isoStandard): RedirectResponse
    {
        if ($isoStandard->sertifikatSistemSemen()->exists()) {
            return back()->with('error', 'Standar ISO ini masih dipakai pada sertifikat sistem, jadi tidak bisa dihapus.');
        }

        $isoStandard->delete();

        return redirect()->route('cement.maintenance.iso-standards.index')->with('success', 'Master standar ISO berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, ?IsoStandard $standard = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('iso_standards', 'code')->ignore($standard?->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
