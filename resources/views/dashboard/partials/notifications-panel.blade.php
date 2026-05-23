<section class="ui-panel ui-dashboard-panel">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="ui-title-sm text-slate-950 dark:text-white">Notifikasi Internal</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                Update terbaru yang perlu diperhatikan tanpa harus membuka seluruh modul monitoring.
            </p>
        </div>

        <a href="{{ route('notifications.index') }}" class="ui-button-secondary px-4 py-2 text-xs">
            Lihat Semua
        </a>
    </div>

    <div class="mt-6 space-y-3">
        @forelse ($notifications as $notification)
            @php($productName = $notification->certificate?->product?->name)
            @php($notificationData = $notification->data ?? [])
            @php($isoLabel = $notification->notification_type === 'cement_system_follow_up' ? trim((string) data_get($notificationData, 'iso_code').' - '.(string) data_get($notificationData, 'location')) : null)
            @php($referenceTime = $notification->scheduled_at ?? $notification->created_at)

            <article class="ui-dashboard-item">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $notification->title }}</p>

                            <span class="ui-badge {{ $notification->read_at ? 'ui-badge-neutral' : 'ui-badge-info' }}">
                                {{ $notification->read_at ? 'Sudah Dibaca' : 'Baru' }}
                            </span>
                        </div>

                        <p class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-300">
                            {{ \Illuminate\Support\Str::limit($notification->message, 140) }}
                        </p>

                        @if ($productName)
                            <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-500">
                                Produk terkait: {{ $productName }}
                            </p>
                        @elseif ($isoLabel)
                            <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-500">
                                ISO terkait: {{ $isoLabel }}
                            </p>
                        @endif
                    </div>

                    <p class="shrink-0 text-xs font-medium text-slate-500 dark:text-slate-500">
                        {{ $referenceTime?->translatedFormat('d M Y, H:i') }}
                    </p>
                </div>
            </article>
        @empty
            <div class="ui-empty-state">
                Belum ada notifikasi internal terbaru. Alert akan muncul otomatis ketika ada sertifikat yang perlu perhatian.
            </div>
        @endforelse
    </div>
</section>
