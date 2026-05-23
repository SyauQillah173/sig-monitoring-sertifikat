@php($user = auth()->user())
@php($hour = now()->hour)
@php($greeting = match (true) {
    $hour < 11 => 'Selamat pagi',
    $hour < 15 => 'Selamat siang',
    $hour < 18 => 'Selamat sore',
    default => 'Selamat malam',
})
@php($displayName = \Illuminate\Support\Str::of($user->name)->explode(' ')->first())

<x-layouts::app :title="__('Dashboard')">
    <div class="ui-page ui-dashboard-page">
        <section class="ui-dashboard-hero">
            <div class="ui-dashboard-hero-head">
                <div class="min-w-0">
                    <p class="ui-dashboard-kicker">Workspace Monitoring</p>
                    <h1 class="ui-dashboard-title">{{ $greeting }}, {{ $displayName }}</h1>
                    <p class="ui-dashboard-copy">{{ $dashboard['subtitle'] }}</p>
                </div>

                <div class="ui-dashboard-meta">
                    <span class="ui-badge ui-dashboard-meta-badge">{{ $user->roleLabel() }}</span>
                </div>
            </div>

            @include('dashboard.partials.summary-cards', ['highlights' => $dashboard['highlights']])
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.18fr_0.82fr]">
            @include('dashboard.partials.chart', [
                'chart' => $dashboard['chart'],
                'summaryNote' => $dashboard['summaryNote'],
            ])

            @include('dashboard.partials.insights-panel', [
                'focusMode' => $dashboard['focusMode'],
                'links' => $dashboard['operationalLinks'],
                'insights' => $dashboard['insights'],
            ])
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.08fr_0.92fr]">
            @include('dashboard.partials.expiring-list', [
                'certificates' => $dashboard['expiringCertificates'],
                'focusMode' => $dashboard['focusMode'],
            ])

            @include('dashboard.partials.notifications-panel', [
                'notifications' => $dashboard['recentNotifications'],
            ])
        </section>
    </div>
</x-layouts::app>
