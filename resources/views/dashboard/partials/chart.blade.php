<section class="ui-panel ui-dashboard-panel ui-dashboard-panel-lg">
    <div>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="ui-title-sm text-slate-950 dark:text-white">Monitoring Overview</h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    Ringkasan visual untuk melihat distribusi status sertifikat dengan cepat.
                </p>
            </div>

            <div class="ui-dashboard-chip-group">
                <span class="ui-dashboard-chip">Live Status</span>
                <span class="ui-dashboard-chip">Responsive</span>
            </div>
        </div>
    </div>

    <div class="ui-dashboard-item mt-6">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-semibold tracking-[0.16em] text-slate-500 uppercase dark:text-slate-400">Status Monitoring</p>
                <p class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">Distribusi kondisi sertifikat saat ini</p>
            </div>

            <p class="max-w-sm text-sm leading-6 text-slate-600 dark:text-slate-400">
                Prioritaskan item berstatus habis dan akan habis untuk menjaga kesiapan dokumen operasional.
            </p>
        </div>
    </div>

    <div class="mt-6 space-y-5">
        @foreach ($chart as $item)
            @php($progressWidth = $item['value'] > 0 ? max($item['width'], 4) : 0)
            <div class="space-y-2">
                <div class="flex items-center justify-between gap-4 text-sm">
                    <span class="font-medium text-slate-800 dark:text-slate-200">{{ $item['label'] }}</span>
                    <span class="text-slate-500 dark:text-slate-400">{{ $item['value'] }}</span>
                </div>
                <div class="ui-dashboard-track">
                    <div
                        @class([
                            'h-full rounded-full shadow-[0_10px_18px_-12px_rgba(15,23,42,0.4)]',
                            $item['color'],
                        ])
                        @style([
                            "width: {$progressWidth}%",
                        ])
                    ></div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="ui-info-strip mt-6">
        {{ $summaryNote }}
    </div>
</section>
