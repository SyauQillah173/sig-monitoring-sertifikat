<x-layouts::app :title="'Import Excel'">
    <div class="ui-page ui-cement-page">
        
        <x-ui.page-header
            eyebrow="Pemeliharaan Data"
            title="Import Excel"
            description="Upload data Sertifikat SNI, TKDN, dan Green Label semen dari template Excel."
        >
            <x-slot:actions>
                <a href="{{ route('cement.exports.template') }}" class="ui-button-secondary">Download Template</a>
            </x-slot:actions>
        </x-ui.page-header>

        @include('admin.master-data.partials.flash-messages')

        <section class="ui-table-shell">
            <div class="grid gap-4 p-6 lg:grid-cols-3">
                <article class="rounded-2xl border border-slate-200/80 bg-white/60 p-4 text-sm text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-200">
                    <p class="font-semibold text-slate-950 dark:text-white">Wajib Diisi</p>
                    <p class="mt-2 leading-6">Isi kolom utama dari dropdown master: kategori, merek, sni, komoditi, lokasi, dan berlaku_sd. TKDN wajib mengisi persentase_tkdn, Green Label wajib mengisi peringkat.</p>
                </article>
                <article class="rounded-2xl border border-slate-200/80 bg-white/60 p-4 text-sm text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-200">
                    <p class="font-semibold text-slate-950 dark:text-white">Tidak Perlu Diisi</p>
                    <p class="mt-2 leading-6">Kolom sisa_hari_otomatis dan status_otomatis hanya rumus bantuan di Excel. Sistem web akan menghitung status sertifikat sendiri.</p>
                </article>
                <article class="rounded-2xl border border-amber-300/70 bg-amber-50/80 p-4 text-sm text-amber-900 dark:border-amber-300/20 dark:bg-amber-300/10 dark:text-amber-100">
                    <p class="font-semibold">File Sertifikat</p>
                    <p class="mt-2 leading-6">Kolom file_sertifikat opsional. Kosongkan saat import biasa, lalu upload PDF/JPG/PNG dari menu Edit Sertifikat setelah data berhasil disimpan.</p>
                </article>
            </div>
        </section>

        <form method="POST" action="{{ route('cement.import.preview') }}" enctype="multipart/form-data" class="ui-form-panel">
            @csrf
            <div class="space-y-2">
                <label for="file_excel" class="ui-label">File Excel</label>
                <input id="file_excel" name="file_excel" type="file" class="ui-input" accept=".xlsx,.xls,.csv" required>
                <p class="ui-table-row-meta">Gunakan template terbaru. Tanggal sebaiknya format yyyy-mm-dd, contoh 2029-06-16. Nilai teks harus sesuai master database; jika belum ada, tambahkan dulu dari menu Pemeliharaan Data.</p>
            </div>
            <div class="mt-6 flex gap-3">
                <button class="ui-button-primary">Preview Data</button>
                @if ($preview && $preview['errors'] === [])
                    <button type="submit" form="cementImportStore" class="ui-button-secondary">Simpan Semua</button>
                @endif
            </div>
        </form>

        <form id="cementImportStore" method="POST" action="{{ route('cement.import.store') }}">@csrf</form>

        @if ($preview)
            <section class="ui-table-shell">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Preview Import</h2>
                    @if ($preview['errors'] !== [])
                        <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                            <p class="font-semibold">Error validasi:</p>
                            <ul class="mt-2 list-disc pl-5">@foreach($preview['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif
                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        <article class="ui-cement-maintenance-card"><span>SNI</span><strong>{{ count($preview['sni']) }}</strong></article>
                        <article class="ui-cement-maintenance-card"><span>TKDN</span><strong>{{ count($preview['tkdn']) }}</strong></article>
                        <article class="ui-cement-maintenance-card"><span>Green Label</span><strong>{{ count($preview['green_label']) }}</strong></article>
                    </div>
                    @if (($preview['skipped_duplicates'] ?? []) !== [])
                        <div class="mt-5 rounded-2xl border border-sky-200/80 bg-sky-50/80 p-4 text-sm text-sky-900 dark:border-sky-300/20 dark:bg-sky-300/10 dark:text-sky-100">
                            <p class="font-semibold">{{ count($preview['skipped_duplicates']) }} data dilewati karena sudah ada atau dobel di file.</p>
                            <ul class="mt-2 list-disc pl-5">
                                @foreach(array_slice($preview['skipped_duplicates'], 0, 8) as $duplicate)
                                    <li>{{ $duplicate['sheet'] }} baris {{ $duplicate['row'] }}: {{ $duplicate['reason'] }}.</li>
                                @endforeach
                            </ul>
                            @if (count($preview['skipped_duplicates']) > 8)
                                <p class="mt-2">Dan {{ count($preview['skipped_duplicates']) - 8 }} data duplikat lainnya.</p>
                            @endif
                        </div>
                    @endif
                    @if (($preview['new_references'] ?? []) !== [])
                        <div class="mt-5 rounded-2xl border border-amber-300/70 bg-amber-50/80 p-4 text-sm text-amber-900 dark:border-amber-300/20 dark:bg-amber-300/10 dark:text-amber-100">
                            <p class="font-semibold">Nilai yang belum ada di master database:</p>
                            <ul class="mt-2 list-disc pl-5">
                                @foreach($preview['new_references'] as $reference)
                                    <li>{{ $reference['label'] }}: {{ $reference['name'] }}</li>
                                @endforeach
                            </ul>
                            <p class="mt-2">Tambahkan dulu dari menu Pemeliharaan Data atau pilih nilai dari dropdown template terbaru.</p>
                        </div>
                    @else
                        <div class="mt-5 rounded-2xl border border-indigo-200/80 bg-indigo-50/80 p-4 text-sm text-indigo-900 dark:border-indigo-300/20 dark:bg-indigo-300/10 dark:text-indigo-100">
                            <p class="font-semibold">Import siap disimpan.</p>
                            <p class="mt-2">Data akan masuk ke database dengan ejaan master yang sudah distandarkan oleh sistem.</p>
                        </div>
                    @endif
                </div>
            </section>
        @endif
    </div>
</x-layouts::app>
