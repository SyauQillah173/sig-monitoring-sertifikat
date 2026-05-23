@php($summary = $landingSummary)
@php($settings = $publicSettings ?? app(\App\Services\SystemSettingService::class)->publicLandingSettings())

<div class="ui-landing-panel-header">
    <div>
        <p class="ui-landing-panel-kicker">System Preview</p>
        <h2 class="ui-landing-panel-title">Monitoring sertifikat.</h2>
        <p class="ui-landing-panel-copy">
            Ringkasan ini membaca data terbaru dari database: SNI, TKDN, Green Label, ISO sistem semen,
            status masa berlaku, dan dokumen yang perlu perhatian.
        </p>
    </div>
</div>

<div class="ui-landing-summary-sections">
    @if (($settings['show_landing_summary_stats'] ?? '1') === '1')
    <section class="ui-landing-summary-block">
        <div class="ui-landing-summary-grid">
            @foreach ($summary['summaryStats'] as $stat)
                <article class="ui-landing-metric-card">
                    <p class="ui-landing-metric-label">{{ $stat['label'] }}</p>
                    <p class="ui-landing-metric-value">{{ number_format($stat['value']) }}</p>
                    <p class="ui-landing-metric-copy">{{ $stat['meta'] }}</p>
                    <span class="ui-landing-metric-dot is-{{ $stat['tone'] }}"></span>
                </article>
            @endforeach
        </div>
    </section>
    @endif

    @if (($settings['show_landing_public_iso'] ?? '1') === '1')
    <section class="ui-landing-subpanel">
        <div class="ui-landing-section-head">
            <div>
                <h3 class="ui-landing-subpanel-title">Sertifikasi Sistem ISO</h3>
                <p class="ui-landing-section-copy">Ringkasan publik sertifikasi sistem manajemen per lokasi/pabrik. File, bukti audit, dan catatan internal tetap hanya tersedia setelah login.</p>
            </div>
            <span class="ui-landing-pill">Public ISO</span>
        </div>

        @if (filled($summary['publicSystemIso']))
            <div class="mt-5 grid gap-3 md:grid-cols-2">
                @foreach ($summary['publicSystemIso'] as $item)
                    <article class="rounded-lg border border-white/20 bg-white/70 p-4 shadow-sm dark:border-white/10 dark:bg-slate-950/40">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ $item['code'] }}</p>
                                <p class="mt-1 text-xs leading-5 text-slate-600 dark:text-slate-300">{{ $item['name'] }}</p>
                            </div>
                            <span class="ui-landing-priority-dot is-{{ $item['tone'] }}"></span>
                        </div>

                        <div class="mt-3 grid gap-2 text-xs leading-5 text-slate-600 dark:text-slate-300">
                            @if (($settings['show_public_iso_location'] ?? '1') === '1')
                                <p><span class="font-semibold text-slate-800 dark:text-slate-100">Lokasi:</span> {{ $item['location'] }}</p>
                            @endif
                            @if (($settings['show_public_iso_issuer'] ?? '1') === '1')
                                <p><span class="font-semibold text-slate-800 dark:text-slate-100">Lembaga:</span> {{ $item['issuer'] }}</p>
                            @endif
                            @if (($settings['show_public_iso_level_year'] ?? '1') === '1')
                                <p><span class="font-semibold text-slate-800 dark:text-slate-100">Tahun:</span> {{ $item['acquisition_year'] }} <span class="mx-1">|</span> {{ $item['level'] }}</p>
                            @endif
                            @if (($settings['show_public_iso_validity'] ?? '1') === '1')
                                <p><span class="font-semibold text-slate-800 dark:text-slate-100">Berlaku s.d:</span> {{ $item['valid_until'] }}</p>
                            @endif
                            @if (($settings['show_public_iso_scope'] ?? '1') === '1')
                                <p><span class="font-semibold text-slate-800 dark:text-slate-100">Scope:</span> {{ $item['scope'] }}</p>
                            @endif
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            @if (($settings['show_public_iso_status'] ?? '1') === '1')
                                <span class="ui-landing-pill">{{ $item['status'] }}</span>
                            @endif
                            @if (($settings['show_public_iso_category'] ?? '1') === '1' && $item['category'])
                                <span class="ui-landing-pill">{{ $item['category'] }}</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="ui-landing-empty">
                Data sertifikasi sistem ISO publik akan tampil setelah sertifikat ISO diinput.
            </div>
        @endif
    </section>
    @endif

    @if (($settings['show_landing_status_monitoring'] ?? '1') === '1' || ($settings['show_landing_document_composition'] ?? '1') === '1')
    <section class="ui-landing-summary-two-column">
        @if (($settings['show_landing_status_monitoring'] ?? '1') === '1')
        <article class="ui-landing-subpanel">
            <div class="ui-landing-section-head">
                <div>
                    <h3 class="ui-landing-subpanel-title">Status Monitoring</h3>
                    <p class="ui-landing-section-copy">Distribusi kondisi sertifikat berdasarkan masa berlaku terbaru.</p>
                </div>
                <span class="ui-landing-pill">Live Status</span>
            </div>

            <div class="ui-landing-status-list">
                @foreach ($summary['statusDistribution'] as $item)
                    <div class="ui-landing-status-item">
                        <div class="ui-landing-status-meta">
                            <div>
                                <p class="ui-landing-status-title">{{ $item['label'] }}</p>
                                <p class="ui-landing-status-copy">{{ $item['note'] }}</p>
                            </div>
                            <div class="text-right">
                                <p class="ui-landing-status-count">{{ number_format($item['value']) }}</p>
                                <p class="ui-landing-status-share">{{ $item['width'] }}%</p>
                            </div>
                        </div>

                        <div class="ui-landing-meter">
                            <div class="ui-landing-meter-bar is-{{ $item['tone'] }}" style="width: {{ $item['width'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>
        @endif

        @if (($settings['show_landing_document_composition'] ?? '1') === '1')
        <article class="ui-landing-subpanel">
            <div class="ui-landing-section-head">
                <div>
                    <h3 class="ui-landing-subpanel-title">Komposisi Dokumen</h3>
                    <p class="ui-landing-section-copy">Jumlah dokumen produk, ISO sistem semen, dan file sertifikat yang tersedia.</p>
                </div>
                <span class="ui-landing-pill">Certificate Data</span>
            </div>

            <div class="ui-landing-followup-list">
                @foreach ($summary['followUps'] as $item)
                    <div class="ui-landing-followup-item">
                        <div class="ui-landing-followup-head">
                            <span class="ui-landing-followup-label">{{ $item['label'] }}</span>
                            <span class="ui-landing-followup-value">{{ number_format($item['value']) }}</span>
                        </div>
                        <div class="ui-landing-meter is-compact">
                            <div class="ui-landing-meter-bar is-{{ $item['tone'] }}" style="width: {{ $item['width'] }}%"></div>
                        </div>
                        <p class="ui-landing-followup-copy">{{ $item['note'] }}</p>
                    </div>
                @endforeach
            </div>
        </article>
        @endif
    </section>
    @endif

    @if (($settings['show_landing_certificate_mix'] ?? '1') === '1' || ($settings['show_landing_priority_feed'] ?? '1') === '1')
    <section class="ui-landing-summary-two-column">
        @if (($settings['show_landing_certificate_mix'] ?? '1') === '1')
        <article class="ui-landing-subpanel">
            <div class="ui-landing-section-head">
                <div>
                    <h3 class="ui-landing-subpanel-title">Distribusi Jenis Sertifikat Semen</h3>
                    <p class="ui-landing-section-copy">Komposisi data SNI, TKDN, Green Label, dan ISO sistem semen yang sedang dipantau.</p>
                </div>
                <span class="ui-landing-pill">Certificate Mix</span>
            </div>

            @if (filled($summary['typeBreakdown']))
                <div class="ui-landing-type-list">
                    @foreach ($summary['typeBreakdown'] as $item)
                        <div class="ui-landing-type-item">
                            <div class="ui-landing-type-head">
                                <div>
                                    <p class="ui-landing-type-title">{{ $item['name'] }}</p>
                                    <p class="ui-landing-type-copy">{{ number_format($item['count']) }} sertifikat</p>
                                </div>
                                <span class="ui-landing-type-share">{{ $item['share'] }}</span>
                            </div>

                            <div class="ui-landing-meter is-soft">
                                <div class="ui-landing-meter-bar is-{{ $item['tone'] }}" style="width: {{ $item['width'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if (filled($summary['systemIsoHighlights']))
                    <div class="mt-5 ui-landing-priority-group">
                        <p class="ui-landing-group-label">ISO Sistem Terpantau</p>
                        <div class="ui-landing-priority-list">
                            @foreach ($summary['systemIsoHighlights'] as $item)
                                <div class="ui-landing-priority-item">
                                    <span class="ui-landing-priority-dot is-{{ $item['tone'] }}"></span>
                                    <div>
                                        <p class="ui-landing-priority-title">{{ $item['code'] }}</p>
                                        @php($systemIsoMeta = collect([
                                            ($settings['show_public_iso_location'] ?? '1') === '1' ? $item['location'] : null,
                                            ($settings['show_public_iso_status'] ?? '1') === '1' ? $item['stage'] : null,
                                            ($settings['show_public_iso_status'] ?? '1') === '1' ? $item['status'] : null,
                                        ])->filter()->implode(' - '))
                                        @if ($systemIsoMeta !== '')
                                            <p class="ui-landing-priority-meta">{{ $systemIsoMeta }}</p>
                                        @endif
                                        <p class="ui-landing-priority-copy">{{ $item['name'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <div class="ui-landing-empty">
                    Jenis sertifikat akan tampil di sini setelah data sertifikat mulai diinput ke database.
                </div>
            @endif
        </article>
        @endif

        @if (($settings['show_landing_priority_feed'] ?? '1') === '1')
        <article class="ui-landing-subpanel">
            <div class="ui-landing-section-head">
                <div>
                    <h3 class="ui-landing-subpanel-title">Prioritas Operasional</h3>
                    <p class="ui-landing-section-copy">Sertifikat terdekat jatuh tempo dan dokumen terbaru yang masuk.</p>
                </div>
                <span class="ui-landing-pill">Priority Feed</span>
            </div>

            <div class="ui-landing-priority-columns">
                <div class="ui-landing-priority-group">
                    <p class="ui-landing-group-label">Mendekati Berlaku Habis</p>

                    @if (filled($summary['priorityCertificates']))
                        <div class="ui-landing-priority-list">
                            @foreach ($summary['priorityCertificates'] as $item)
                                <div class="ui-landing-priority-item">
                                    <span class="ui-landing-priority-dot is-{{ $item['tone'] }}"></span>
                                    <div>
                                        <p class="ui-landing-priority-title">{{ $item['title'] }}</p>
                                        @if ($item['is_system_iso'] ?? false)
                                            @php($priorityMeta = collect([
                                                ($settings['show_public_iso_status'] ?? '1') === '1' ? $item['status'] : null,
                                                ($settings['show_public_iso_validity'] ?? '1') === '1' ? $item['valid_until'] : null,
                                            ])->filter()->implode(' - '))
                                            @if ($priorityMeta !== '')
                                                <p class="ui-landing-priority-meta">{{ $priorityMeta }}</p>
                                            @endif
                                            @if (($settings['show_public_iso_scope'] ?? '1') === '1')
                                                <p class="ui-landing-priority-copy">{{ $item['note'] }}</p>
                                            @endif
                                        @else
                                            <p class="ui-landing-priority-meta">{{ $item['meta'] }}</p>
                                            <p class="ui-landing-priority-copy">{{ $item['note'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="ui-landing-empty is-compact">
                            Belum ada item jatuh tempo yang perlu ditonjolkan.
                        </div>
                    @endif
                </div>

                <div class="ui-landing-priority-group">
                    <p class="ui-landing-group-label">Dokumen Terbaru</p>

                    @if (filled($summary['recentDocuments']))
                        <div class="ui-landing-priority-list">
                            @foreach ($summary['recentDocuments'] as $item)
                                <div class="ui-landing-priority-item">
                                    <span class="ui-landing-priority-dot is-{{ $item['tone'] }}"></span>
                                    <div>
                                        <p class="ui-landing-priority-title">{{ $item['title'] }}</p>
                                        @if (! ($item['is_system_iso'] ?? false) || ($settings['show_public_iso_location'] ?? '1') === '1')
                                            <p class="ui-landing-priority-meta">{{ $item['meta'] }}</p>
                                        @endif
                                        <p class="ui-landing-priority-copy">{{ $item['note'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="ui-landing-empty is-compact">
                            Riwayat dokumen akan muncul setelah file sertifikat mulai diunggah.
                        </div>
                    @endif
                </div>
            </div>
        </article>
        @endif
    </section>
    @endif
</div>
