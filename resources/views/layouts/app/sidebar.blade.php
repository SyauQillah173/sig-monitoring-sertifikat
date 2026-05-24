<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="ui-workspace-shell" data-sidebar-open="false" data-sidebar-collapsed="false">
        @php($user = auth()->user())
        @php($dashboardRoute = $user->dashboardRouteName())
        @php($navigationGroups = $navigationGroups ?? app(\App\Services\SystemNavigationService::class)->groupsForUser($user))

        <div class="ui-workspace-grid">
            <button type="button" class="ui-workspace-backdrop" data-sidebar-close aria-label="Tutup navigasi"></button>

            <aside class="ui-workspace-sidebar" id="workspace-sidebar">
                <div class="ui-workspace-sidebar-inner">
                    <button type="button" class="ui-workspace-sidebar-close" data-sidebar-close aria-label="Tutup navigasi">
                        <flux:icon name="x-mark" variant="outline" class="size-5" />
                    </button>

                    <div class="ui-workspace-sidebar-scroll">
                        <a href="{{ route($dashboardRoute) }}" class="ui-workspace-brand" wire:navigate.hover>
                            <span class="ui-workspace-brand-mark">
                                <x-app-logo-icon class="h-6 w-auto" />
                            </span>

                            <span class="ui-workspace-brand-copy min-w-0">
                                <span class="block ui-workspace-brand-kicker">Internal Monitoring Platform</span>
                                <span class="block text-sm font-semibold tracking-[0.1em] text-slate-900 uppercase dark:text-white">
                                    {{ config('app.name', 'SIG Monitoring Sertifikat') }}
                                </span>
                            </span>

                            <span class="ui-workspace-brand-compact" aria-hidden="true">SIG</span>
                        </a>

                        <nav class="ui-workspace-nav" aria-label="Navigasi utama">
                            @foreach ($navigationGroups as $group)
                                <section class="ui-workspace-nav-group">
                                    <p class="ui-workspace-nav-label">{{ $group['label'] }}</p>

                                    <div class="ui-workspace-nav-list">
                                        @foreach ($group['items'] as $item)
                                            <a
                                                href="{{ $item['route'] }}"
                                                @class([
                                                    'ui-workspace-nav-item',
                                                    'is-active' => $item['current'],
                                                ])
                                                wire:navigate.hover
                                            >
                                                <span class="ui-workspace-nav-icon">
                                                    <flux:icon :name="$item['icon']" variant="outline" class="size-5" />
                                                </span>
                                                <span class="ui-workspace-nav-text min-w-0 flex-1 text-inherit">{{ $item['label'] }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </nav>
                    </div>
                </div>
            </aside>

            <div class="ui-workspace-main">
                <header class="ui-workspace-topbar">
                    <div class="ui-workspace-topbar-inner">
                        <div class="ui-workspace-topbar-context">
                            <button type="button" class="ui-workspace-topbar-toggle" data-sidebar-toggle aria-label="Buka atau tutup navigasi">
                                <flux:icon name="bars-3" variant="outline" class="size-5" />
                            </button>

                            <div class="min-w-0">
                                <p class="ui-workspace-topbar-title">{{ $title ?? config('app.name', 'SIG Monitoring Sertifikat') }}</p>
                            </div>
                        </div>

                        <div class="ui-workspace-topbar-actions">
                            <div x-data class="ui-workspace-theme-wrap">
                                <button
                                    type="button"
                                    class="ui-workspace-theme-toggle"
                                    x-on:click="$flux.appearance = $flux.appearance === 'dark' ? 'light' : 'dark'"
                                >
                                    <span class="ui-workspace-theme-icon">
                                        <flux:icon name="sun" variant="outline" class="size-4" x-show="$flux.appearance === 'dark'" x-cloak />
                                        <flux:icon name="moon" variant="outline" class="size-4" x-show="$flux.appearance !== 'dark'" x-cloak />
                                    </span>
                                </button>
                            </div>


                            <details class="ui-workspace-user-menu">
                                <summary class="ui-workspace-user-trigger">
                                    <span class="ui-workspace-user-avatar">{{ $user->initials() }}</span>

                                    <span class="ui-workspace-user-trigger-copy">
                                        <span class="block text-sm font-semibold text-slate-900 dark:text-white">{{ $user->name }}</span>
                                        <span class="block text-[0.68rem] font-semibold tracking-[0.12em] text-slate-500 uppercase dark:text-slate-400">{{ $user->roleLabel() }}</span>
                                    </span>

                                    <flux:icon name="chevron-down" variant="mini" class="size-4 text-slate-500 dark:text-slate-400" />
                                </summary>

                                <div class="ui-workspace-user-dropdown">
                                    <div class="ui-workspace-user-dropdown-card">
                                        <p class="ui-workspace-user-dropdown-name">{{ $user->name }}</p>
                                        <p class="ui-workspace-user-dropdown-email">{{ $user->email }}</p>
                                        <div class="mt-3">
                                            <span class="ui-badge ui-badge-neutral">{{ $user->roleLabel() }}</span>
                                        </div>
                                    </div>

                                    <div class="ui-workspace-user-dropdown-actions">
                                        <a href="{{ route('profile.edit') }}" class="ui-workspace-user-dropdown-link" wire:navigate.hover>
                                            <flux:icon name="cog-6-tooth" variant="outline" class="size-4" />
                                            <span>Pengaturan Akun</span>
                                        </a>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf

                                            <button type="submit" class="ui-workspace-user-dropdown-link w-full text-left" data-test="logout-button">
                                                <flux:icon name="arrow-right-start-on-rectangle" variant="outline" class="size-4" />
                                                <span>Keluar</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>
                </header>

                <main class="ui-workspace-content">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <x-ui.developer-footer class="ui-developer-footer-workspace" />

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        <x-ui.confirm-modal />

        @fluxScripts
    </body>
</html>
