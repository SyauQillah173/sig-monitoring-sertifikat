<x-layouts::app :title="'Tampilan Publik'">
    <div class="ui-page">
        <x-ui.page-header
            eyebrow="Pengaturan Sistem"
            title="Tampilan Publik"
            description="Atur teks dan branding halaman sebelum login. Data ringkasan sertifikat tetap otomatis membaca database."
        />

        @include('admin.master-data.partials.flash-messages')
        @include('cement.maintenance.certificates.shared-errors')

        <form method="POST" action="{{ route('system-settings.public-appearance.update') }}" class="ui-form-panel">
            @csrf
            @method('PUT')

            <div class="grid gap-5 md:grid-cols-2">
                <div class="space-y-2">
                    <label class="ui-label" for="public_brand_kicker">Brand Kicker</label>
                    <input id="public_brand_kicker" name="public_brand_kicker" value="{{ old('public_brand_kicker', $settings['public_brand_kicker']) }}" class="ui-input" required>
                </div>
                <div class="space-y-2">
                    <label class="ui-label" for="public_brand_name">Nama Brand</label>
                    <input id="public_brand_name" name="public_brand_name" value="{{ old('public_brand_name', $settings['public_brand_name']) }}" class="ui-input" required>
                </div>
                <div class="space-y-2">
                    <label class="ui-label" for="landing_badge">Badge Landing</label>
                    <input id="landing_badge" name="landing_badge" value="{{ old('landing_badge', $settings['landing_badge']) }}" class="ui-input" required>
                </div>
                <div class="space-y-2">
                    <label class="ui-label" for="footer_text">Footer</label>
                    <input id="footer_text" name="footer_text" value="{{ old('footer_text', $settings['footer_text']) }}" class="ui-input" required>
                </div>
            </div>

            <div class="mt-5 space-y-2">
                <label class="ui-label" for="landing_title">Judul Hero</label>
                <input id="landing_title" name="landing_title" value="{{ old('landing_title', $settings['landing_title']) }}" class="ui-input" required>
            </div>

            <div class="mt-5 space-y-2">
                <label class="ui-label" for="landing_description">Deskripsi Hero</label>
                <textarea id="landing_description" name="landing_description" rows="4" class="ui-input" required>{{ old('landing_description', $settings['landing_description']) }}</textarea>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-3">
                @for ($i = 1; $i <= 3; $i++)
                    <div class="space-y-4 rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                        <h2 class="ui-title-sm">Value {{ $i }}</h2>
                        <div class="space-y-2">
                            <label class="ui-label" for="landing_value_{{ $i }}_title">Judul</label>
                            <input id="landing_value_{{ $i }}_title" name="landing_value_{{ $i }}_title" value="{{ old('landing_value_'.$i.'_title', $settings['landing_value_'.$i.'_title']) }}" class="ui-input" required>
                        </div>
                        <div class="space-y-2">
                            <label class="ui-label" for="landing_value_{{ $i }}_body">Isi</label>
                            <textarea id="landing_value_{{ $i }}_body" name="landing_value_{{ $i }}_body" rows="3" class="ui-input" required>{{ old('landing_value_'.$i.'_body', $settings['landing_value_'.$i.'_body']) }}</textarea>
                        </div>
                    </div>
                @endfor
            </div>

            <section class="mt-6 rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                <div class="mb-4">
                    <h2 class="ui-title-sm">Section Landing Sebelum Login</h2>
                    <p class="ui-input-hint mt-1">Matikan bagian yang belum dibutuhkan. Data tetap aman di database dan bisa ditampilkan lagi kapan saja.</p>
                </div>

                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @php($visibilityOptions = [
                        'show_landing_summary_stats' => 'Ringkasan Angka',
                        'show_landing_status_monitoring' => 'Status Monitoring',
                        'show_landing_document_composition' => 'Komposisi Dokumen',
                        'show_landing_certificate_mix' => 'Distribusi Jenis Sertifikat',
                        'show_landing_public_iso' => 'Sertifikasi Sistem ISO',
                        'show_landing_priority_feed' => 'Prioritas Operasional',
                    ])

                    @foreach ($visibilityOptions as $key => $label)
                        <label class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                            <span>{{ $label }}</span>
                            <input type="hidden" name="{{ $key }}" value="0">
                            <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $settings[$key] ?? '1') === '1')>
                        </label>
                    @endforeach
                </div>

                <div class="mt-6 border-t border-slate-200 pt-5 dark:border-slate-700">
                    <h3 class="ui-title-sm">Privasi Data ISO Publik</h3>
                    <p class="ui-input-hint mt-1">Atur detail ISO mana yang boleh muncul di halaman sebelum login. Nomor sertifikat, file sertifikat, bukti audit, dan catatan internal tetap tidak ditampilkan.</p>

                    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @php($publicIsoPrivacyOptions = [
                            'show_public_iso_location' => 'Lokasi / Pabrik',
                            'show_public_iso_issuer' => 'Lembaga Sertifikasi',
                            'show_public_iso_scope' => 'Scope',
                            'show_public_iso_validity' => 'Masa Berlaku',
                            'show_public_iso_status' => 'Status Masa Berlaku',
                            'show_public_iso_level_year' => 'Tahun & Level',
                            'show_public_iso_category' => 'Kategori Sertifikasi',
                        ])

                        @foreach ($publicIsoPrivacyOptions as $key => $label)
                            <label class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                <span>{{ $label }}</span>
                                <input type="hidden" name="{{ $key }}" value="0">
                                <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $settings[$key] ?? '1') === '1')>
                            </label>
                        @endforeach
                    </div>
                </div>
            </section>

            <div class="mt-6 flex flex-wrap gap-3">
                <button class="ui-button-primary">Simpan Tampilan Publik</button>
                <a href="{{ route('home') }}" class="ui-button-secondary" target="_blank">Preview Landing</a>
            </div>
        </form>
    </div>
</x-layouts::app>
