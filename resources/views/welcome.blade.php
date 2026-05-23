<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php($title = 'Beranda')
        @include('partials.head')
    </head>
    <body class="ui-landing-shell">
        <div class="ui-landing-grid"></div>
        <div class="ui-landing-orb ui-landing-orb-primary"></div>
        <div class="ui-landing-orb ui-landing-orb-secondary"></div>

        <div class="ui-landing-container">
            <header class="ui-landing-nav">
                <div class="ui-landing-brand">
                    <div class="ui-landing-brand-mark">
                        <x-app-logo-icon class="h-7 w-auto" />
                    </div>
                    <div>
                        <p class="ui-landing-brand-kicker">{{ $publicSettings['public_brand_kicker'] }}</p>
                        <p class="ui-landing-brand-name">{{ $publicSettings['public_brand_name'] }}</p>
                    </div>
                </div>

                <div class="ui-landing-actions">
                    <div x-data class="ui-public-theme-wrap">
                        <button
                            type="button"
                            class="ui-public-theme-toggle"
                            x-on:click="$flux.appearance = $flux.appearance === 'dark' ? 'light' : 'dark'"
                        >
                            <span class="ui-public-theme-icon">
                                <flux:icon name="sun" variant="outline" class="size-4" x-show="$flux.appearance === 'dark'" x-cloak />
                                <flux:icon name="moon" variant="outline" class="size-4" x-show="$flux.appearance !== 'dark'" x-cloak />
                            </span>
                        </button>
                    </div>

                    @auth
                        <a href="{{ route('dashboard') }}" class="ui-landing-login">
                            Masuk ke Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="ui-landing-login">
                            Login Sistem
                        </a>
                    @endauth
                </div>
            </header>

            <main class="ui-landing-main">
                <div class="ui-landing-hero">
                    <section class="ui-landing-story">
                        <x-landing-indonesia-map />

                        <div class="ui-landing-badge">
                            {{ $publicSettings['landing_badge'] }}
                        </div>

                        <h1 class="ui-landing-title">
                            {{ $publicSettings['landing_title'] }}
                        </h1>

                        <p class="ui-landing-copy">
                            {{ $publicSettings['landing_description'] }}
                        </p>

                        <div class="ui-landing-values">
                            <div class="ui-landing-value">
                                <span class="ui-landing-value-label"></span>
                                <p class="ui-landing-value-copy">
                                    <span class="font-semibold text-slate-950 dark:text-white">{{ $publicSettings['landing_value_1_title'] }}</span> {{ $publicSettings['landing_value_1_body'] }}
                                </p>
                            </div>
                            <div class="ui-landing-value">
                                <span class="ui-landing-value-label"></span>
                                <p class="ui-landing-value-copy">
                                    <span class="font-semibold text-slate-950 dark:text-white">{{ $publicSettings['landing_value_2_title'] }}</span> {{ $publicSettings['landing_value_2_body'] }}
                                </p>
                            </div>
                            <div class="ui-landing-value">
                                <span class="ui-landing-value-label"></span>
                                <p class="ui-landing-value-copy">
                                    <span class="font-semibold text-slate-950 dark:text-white">{{ $publicSettings['landing_value_3_title'] }}</span> {{ $publicSettings['landing_value_3_body'] }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section id="overview" class="ui-landing-panel ui-landing-panel-wide min-w-0" data-landing-summary data-summary-url="{{ route('home.summary') }}">
                        @include('landing.partials.summary-panel', ['landingSummary' => $landingSummary])
                    </section>
                </div>
            </main>

            <x-ui.developer-footer :text="$publicSettings['footer_text']" />
        </div>

        <script>
            (() => {
                const summary = document.querySelector('[data-landing-summary]');
                if (!summary) return;

                let isRefreshing = false;

                const refreshSummary = async () => {
                    if (document.hidden || isRefreshing) return;

                    isRefreshing = true;

                    try {
                        const response = await fetch(summary.dataset.summaryUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html',
                            },
                            cache: 'no-store',
                        });

                        if (response.ok) {
                            summary.innerHTML = await response.text();
                        }
                    } catch (error) {
                        console.warn('Landing summary refresh skipped.', error);
                    } finally {
                        isRefreshing = false;
                    }
                };

                window.setInterval(refreshSummary, 120000);
                document.addEventListener('visibilitychange', refreshSummary);
            })();
        </script>

        @fluxScripts
    </body>
</html>
