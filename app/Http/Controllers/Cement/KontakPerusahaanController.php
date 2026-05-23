<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Models\KontakPerusahaan;
use App\Models\PerusahaanSemen;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KontakPerusahaanController extends Controller
{
    public function index(Request $request): View
    {
        return view('cement.maintenance.company-contacts.index', [
            'contacts' => KontakPerusahaan::query()
                ->with('perusahaanSemen')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = '%'.$request->string('search').'%';

                    $query->where(fn ($subQuery) => $subQuery
                        ->where('nama_pic', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhereHas('perusahaanSemen', fn ($companyQuery) => $companyQuery->where('nama_perusahaan', 'like', $search)));
                })
                ->orderBy('nama_pic')
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('cement.maintenance.company-contacts.create', [
            'contact' => new KontakPerusahaan(['is_active' => true]),
            'companies' => $this->companies(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        KontakPerusahaan::query()->create($this->validatedPayload($request));

        return redirect()->route('cement.maintenance.kontak-perusahaan.index')->with('success', 'Kontak perusahaan berhasil ditambahkan.');
    }

    public function edit(KontakPerusahaan $kontakPerusahaan): View
    {
        return view('cement.maintenance.company-contacts.edit', [
            'contact' => $kontakPerusahaan,
            'companies' => $this->companies(),
        ]);
    }

    public function update(Request $request, KontakPerusahaan $kontakPerusahaan): RedirectResponse
    {
        $kontakPerusahaan->update($this->validatedPayload($request, $kontakPerusahaan));

        return redirect()->route('cement.maintenance.kontak-perusahaan.index')->with('success', 'Kontak perusahaan berhasil diperbarui.');
    }

    public function destroy(KontakPerusahaan $kontakPerusahaan): RedirectResponse
    {
        $kontakPerusahaan->delete();

        return redirect()->route('cement.maintenance.kontak-perusahaan.index')->with('success', 'Kontak perusahaan berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, ?KontakPerusahaan $contact = null): array
    {
        return $request->validate([
            'perusahaan_semen_id' => ['required', 'integer', Rule::exists('perusahaan_semen', 'id')],
            'nama_pic' => ['required', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('kontak_perusahaan', 'email')
                    ->where('perusahaan_semen_id', $request->integer('perusahaan_semen_id'))
                    ->ignore($contact?->id),
            ],
            'phone' => ['nullable', 'string', 'max:80'],
            'is_primary' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function companies()
    {
        return PerusahaanSemen::query()
            ->where('is_active', true)
            ->orderBy('nama_perusahaan')
            ->get();
    }
}
