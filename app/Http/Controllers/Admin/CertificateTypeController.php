<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCertificateTypeRequest;
use App\Http\Requests\Admin\UpdateCertificateTypeRequest;
use App\Models\CertificateType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Throwable;

class CertificateTypeController extends Controller
{
    public function index(): View
    {
        return view('admin.master-data.certificate-types.index', [
            'certificateTypes' => CertificateType::query()
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('admin.master-data.certificate-types.create', [
            'certificateType' => new CertificateType,
        ]);
    }

    public function store(StoreCertificateTypeRequest $request): RedirectResponse
    {
        try {
            CertificateType::query()->create($request->validated());

            return redirect()
                ->route('admin.certificate-types.index')
                ->with('success', 'Jenis sertifikat berhasil ditambahkan.');
        } catch (Throwable $throwable) {
            report($throwable);

            return back()
                ->withInput()
                ->with('error', 'Jenis sertifikat gagal disimpan. Silakan coba lagi.');
        }
    }

    public function edit(CertificateType $certificateType): View
    {
        return view('admin.master-data.certificate-types.edit', [
            'certificateType' => $certificateType,
        ]);
    }

    public function update(UpdateCertificateTypeRequest $request, CertificateType $certificateType): RedirectResponse
    {
        try {
            $certificateType->update($request->validated());

            return redirect()
                ->route('admin.certificate-types.index')
                ->with('success', 'Jenis sertifikat berhasil diperbarui.');
        } catch (Throwable $throwable) {
            report($throwable);

            return back()
                ->withInput()
                ->with('error', 'Jenis sertifikat gagal diperbarui. Silakan coba lagi.');
        }
    }

    public function destroy(CertificateType $certificateType): RedirectResponse
    {
        try {
            $certificateType->delete();

            return redirect()
                ->route('admin.certificate-types.index')
                ->with('success', 'Jenis sertifikat berhasil dihapus.');
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('error', 'Jenis sertifikat tidak dapat dihapus karena masih digunakan.');
        }
    }
}
