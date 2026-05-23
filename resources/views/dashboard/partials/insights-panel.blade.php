<section class="ui-panel ui-dashboard-panel">
    <div class="flex flex-col gap-3">
        <div>
            <h2 class="ui-title-sm text-slate-950 dark:text-white">Insight Cepat</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                Ringkasan otomatis untuk membantu menentukan prioritas tindak lanjut berikutnya.
            </p>
        </div>

        @if ($focusMode === 'operational' && filled($links))
            <div class="flex flex-wrap gap-3">
                @foreach ($links as $link)
                    <a
                        href="{{ $link['href'] }}"
                        @class([
                            'ui-button-primary' => $link['style'] === 'primary',
                            'ui-button-secondary' => $link['style'] !== 'primary',
                        ])
                    >
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="mt-6 space-y-3">
        @foreach ($insights as $insight)
            <article class="ui-dashboard-item">
                <div class="flex items-start gap-3">
                    <span @class([
                        'mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full',
                        'bg-emerald-400' => $insight['tone'] === 'success',
                        'bg-amber-400' => $insight['tone'] === 'warning',
                        'bg-rose-400' => $insight['tone'] === 'danger',
                        'bg-cyan-400' => $insight['tone'] === 'info',
                    ])></span>

                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $insight['title'] }}</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-400">{{ $insight['copy'] }}</p>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</section>
