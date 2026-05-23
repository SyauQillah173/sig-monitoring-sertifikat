<section class="ui-panel ui-dashboard-panel">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="ui-title-sm text-slate-950 dark:text-white">5 Sertifikat Terdekat Habis</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                {{ $focusMode === 'operational'
                    ? 'Daftar kerja prioritas untuk segera ditindaklanjuti oleh petugas.'
                    : 'Daftar sertifikat yang perlu perhatian karena masa berlakunya paling dekat.' }}
            </p>
        </div>

        @if (Route::has('certificates.index') && auth()->user()->hasAnyAppRole([\App\Enums\UserRole::Admin, \App\Enums\UserRole::Petugas]))
            <a href="{{ route('certificates.index', ['status' => 'expiring_soon']) }}" class="ui-button-secondary px-4 py-2 text-xs">
                Lihat Semua
            </a>
        @endif
    </div>

    <div class="mt-6 space-y-4">
        @forelse ($certificates as $certificate)
            <div class="ui-dashboard-item">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $certificate->certificate_number }}</p>
                        <p class="text-sm text-slate-800 dark:text-slate-200">{{ $certificate->product->name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $certificate->certificateType->name }} &middot; {{ $certificate->issuer->name }}
                        </p>
                    </div>

                    <div class="text-left md:text-right">
                        <span class="{{ $certificate->statusBadgeClasses() }}">
                            {{ $certificate->statusLabel() }}
                        </span>
                        <p class="mt-2 text-sm font-medium text-slate-900 dark:text-white">{{ $certificate->expires_at->format('d M Y') }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $certificate->expiryCountdownLabel() }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="ui-empty-state">
                Belum ada sertifikat aktif yang mendekati masa habis berlaku.
            </div>
        @endforelse
    </div>
</section>
