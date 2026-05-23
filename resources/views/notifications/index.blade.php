<x-layouts::app :title="'Notifikasi Internal'">
    <div class="ui-page">
        <x-ui.page-header
            eyebrow="Notifikasi Sistem"
            title="Notifikasi Internal"
            description="Pusat tindak lanjut sertifikat sistem ISO, reminder masa berlaku, surveilen, dan renewal."
        >
            <x-slot:actions>
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="ui-button-primary">
                        Tandai Semua Sudah Dibaca
                    </button>
                </form>
            </x-slot:actions>
        </x-ui.page-header>

        @include('admin.master-data.partials.flash-messages')

        <div class="space-y-4">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data ?? [];
                    $isSystemFollowUp = $notification->notification_type === 'cement_system_follow_up';
                    $isUnread = $notification->status === \App\Enums\NotificationStatus::Unread;
                @endphp

                <article class="ui-panel">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0 flex-1 space-y-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <h2 class="ui-title-sm">{{ $notification->title }}</h2>
                                <span class="{{ $isUnread ? 'ui-badge ui-badge-danger' : 'ui-badge ui-badge-success' }}">
                                    {{ $isUnread ? 'Perlu Tindak Lanjut' : 'Sudah Diproses' }}
                                </span>
                                @if ($isSystemFollowUp)
                                    <span class="ui-badge ui-badge-info">{{ data_get($data, 'follow_up_label') }}</span>
                                @endif
                            </div>

                            <p class="text-sm leading-6 text-slate-700 dark:text-slate-300">{{ $notification->message }}</p>

                            @if ($isSystemFollowUp)
                                <div class="grid gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4 text-sm dark:border-white/10 dark:bg-white/5 md:grid-cols-4">
                                    <div>
                                        <p class="ui-table-row-meta">Standar ISO</p>
                                        <p class="font-semibold text-slate-950 dark:text-white">{{ data_get($data, 'iso_code') }} - {{ data_get($data, 'iso_name') }}</p>
                                    </div>
                                    <div>
                                        <p class="ui-table-row-meta">Lokasi/Pabrik</p>
                                        <p class="font-semibold text-slate-950 dark:text-white">{{ data_get($data, 'location') }}</p>
                                    </div>
                                    <div>
                                        <p class="ui-table-row-meta">Target</p>
                                        <p class="font-semibold text-slate-950 dark:text-white">{{ data_get($data, 'target_date_label') }}</p>
                                    </div>
                                    <div>
                                        <p class="ui-table-row-meta">Nomor Sertifikat</p>
                                        <p class="font-semibold text-slate-950 dark:text-white">{{ data_get($data, 'certificate_number') }}</p>
                                    </div>
                                </div>
                            @elseif ($notification->certificate)
                                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4 text-sm text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                                    <p>Sertifikat: {{ $notification->certificate->certificate_number }}</p>
                                    <p>Produk: {{ $notification->certificate->product?->name ?? '-' }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="flex shrink-0 flex-col gap-3 xl:items-end">
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $notification->created_at->format('d M Y H:i') }}
                            </p>

                            @if ($isSystemFollowUp && data_get($data, 'action_url') && auth()->user()->hasAnyAppRole([\App\Enums\UserRole::Admin, \App\Enums\UserRole::Petugas]))
                                <a href="{{ data_get($data, 'action_url') }}" class="ui-button-primary px-4 py-2 text-xs">
                                    {{ data_get($data, 'follow_up_label', 'Tindak Lanjut') }}
                                </a>
                            @elseif ($notification->certificate && auth()->user()->hasAnyAppRole([\App\Enums\UserRole::Admin, \App\Enums\UserRole::Petugas]))
                                <a href="{{ route('certificates.show', $notification->certificate) }}" class="ui-button-secondary px-4 py-2 text-xs">
                                    Lihat Sertifikat
                                </a>
                            @endif

                            @if ($isUnread)
                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="ui-button-secondary px-4 py-2 text-xs">
                                        Tandai Sudah Dibaca
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="ui-empty-state">
                    Belum ada notifikasi internal.
                </div>
            @endforelse
        </div>

        <div class="ui-pagination-card">
            {{ $notifications->links() }}
        </div>
    </div>
</x-layouts::app>
