<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIssuerRequest;
use App\Http\Requests\Admin\UpdateIssuerRequest;
use App\Models\Issuer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Throwable;

class IssuerController extends Controller
{
    public function index(): View
    {
        return view('admin.master-data.issuers.index', [
            'issuers' => Issuer::query()
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('admin.master-data.issuers.create', [
            'issuer' => new Issuer,
        ]);
    }

    public function store(StoreIssuerRequest $request): RedirectResponse
    {
        try {
            Issuer::query()->create($request->validated());

            return redirect()
                ->route('admin.issuers.index')
                ->with('success', 'Lembaga penerbit berhasil ditambahkan.');
        } catch (Throwable $throwable) {
            report($throwable);

            return back()
                ->withInput()
                ->with('error', 'Lembaga penerbit gagal disimpan. Silakan coba lagi.');
        }
    }

    public function edit(Issuer $issuer): View
    {
        return view('admin.master-data.issuers.edit', [
            'issuer' => $issuer,
        ]);
    }

    public function update(UpdateIssuerRequest $request, Issuer $issuer): RedirectResponse
    {
        try {
            $issuer->update($request->validated());

            return redirect()
                ->route('admin.issuers.index')
                ->with('success', 'Lembaga penerbit berhasil diperbarui.');
        } catch (Throwable $throwable) {
            report($throwable);

            return back()
                ->withInput()
                ->with('error', 'Lembaga penerbit gagal diperbarui. Silakan coba lagi.');
        }
    }

    public function destroy(Issuer $issuer): RedirectResponse
    {
        try {
            $issuer->delete();

            return redirect()
                ->route('admin.issuers.index')
                ->with('success', 'Lembaga penerbit berhasil dihapus.');
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('error', 'Lembaga penerbit tidak dapat dihapus karena masih digunakan.');
        }
    }
}
