<section class="ui-stat-grid ui-dashboard-summary-grid">
    @foreach ($highlights as $item)
        <x-ui.metric-card
            :label="$item['label']"
            :value="$item['value']"
            :tone="match ($item['state']) {
                'ready' => 'success',
                'limited', 'warning' => 'warning',
                'critical' => 'danger',
                'locked' => 'info',
                default => 'default',
            }"
        />
    @endforeach
</section>
