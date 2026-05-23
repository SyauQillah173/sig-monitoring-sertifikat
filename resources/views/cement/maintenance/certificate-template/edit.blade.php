<x-layouts::app :title="'Template Sertifikat'">
    <div class="ui-page">
        <x-ui.page-header
            eyebrow="Pemeliharaan Data"
            title="Template Sertifikat"
            description="Upload gambar background sertifikat. Sistem akan otomatis menaruh teks sertifikat di atas template aktif."
        />

        @include('admin.master-data.partials.flash-messages')
        @include('cement.maintenance.certificates.shared-errors')

        <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
            <form method="POST" action="{{ route('cement.maintenance.certificate-template.update') }}" enctype="multipart/form-data" class="ui-form-panel">
                @csrf
                @method('PUT')

                <div class="space-y-2">
                    <label for="template" class="ui-label">Upload Template Sertifikat</label>
                    <input id="template" name="template" type="file" class="ui-input" accept=".jpg,.jpeg,.png,.webp" required>
                    <p class="ui-table-row-meta">
                        Gunakan gambar portrait A4 agar teks otomatis pas. Rekomendasi rasio 210:297, format JPG/PNG/WEBP, maksimal 12 MB.
                    </p>
                </div>

                <div class="mt-6 rounded-2xl border border-indigo-200/80 bg-indigo-50/80 p-4 text-sm text-indigo-900 dark:border-indigo-300/20 dark:bg-indigo-300/10 dark:text-indigo-100">
                    <p class="font-semibold">Cara kerja otomatis:</p>
                    <p class="mt-2">Setelah template diupload, semua tombol Dokumen pada SNI, TKDN, Green Label, dan ISO akan memakai template baru. Data sertifikat tetap diambil dari database.</p>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <button class="ui-button-primary">Simpan Template</button>
                </div>
            </form>

            <aside class="ui-form-panel">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Template Aktif</h2>
                <p class="ui-table-row-meta mt-1">{{ $templatePath }}</p>

                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-white/10 dark:bg-slate-950">
                    <img src="{{ asset($templatePath) }}" alt="Template sertifikat aktif" class="block w-full">
                </div>

                <form method="POST" action="{{ route('cement.maintenance.certificate-template.reset') }}" class="mt-5" data-confirm data-confirm-title="Konfirmasi Reset" data-confirm-message="Kembalikan template sertifikat ke default?" data-confirm-action="Reset" data-confirm-loading-label="Mereset...">
                    @csrf
                    @method('DELETE')
                    <button class="ui-button-danger w-full" @disabled($templatePath === $defaultTemplate)>Reset ke Default</button>
                </form>
            </aside>
        </section>
    </div>
</x-layouts::app>
