@props([
    'label',
    'value',
    'tone' => 'default',
])

<div class="ui-kpi-card">
    <div class="flex items-center justify-between gap-3">
        <p class="ui-kpi-label">{{ $label }}</p>
        <span @class([
            'inline-flex h-2.5 w-2.5 rounded-full',
            'bg-slate-500' => $tone === 'default',
            'bg-emerald-400' => $tone === 'success',
            'bg-amber-400' => $tone === 'warning',
            'bg-rose-400' => $tone === 'danger',
            'bg-cyan-400' => $tone === 'info',
        ])></span>
    </div>
    <p @class([
        'ui-kpi-value',
        'text-white' => $tone === 'default',
        'text-emerald-200' => $tone === 'success',
        'text-amber-200' => $tone === 'warning',
        'text-rose-200' => $tone === 'danger',
        'text-cyan-200' => $tone === 'info',
    ])>{{ $value }}</p>
</div>
