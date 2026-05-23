@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'backHref' => null,
    'backLabel' => 'Kembali',
])

@php
    $resolvedBackHref = $backHref;

    if (! $resolvedBackHref && request()->routeIs('cement.maintenance.*') && ! request()->routeIs('cement.maintenance.index')) {
        $resolvedBackHref = route('cement.maintenance.index');
    }
@endphp

<section {{ $attributes->class(['ui-hero']) }}>
    <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0">
            @if (filled($eyebrow))
                <p class="ui-section-label">{{ $eyebrow }}</p>
            @endif
            <h1 class="ui-title">{{ $title }}</h1>
            @if (filled($description))
                <p class="ui-subtitle">{{ $description }}</p>
            @endif
        </div>

        @if ($resolvedBackHref || isset($actions))
            <div class="flex shrink-0 flex-wrap gap-3 xl:max-w-md xl:justify-end">
                @if ($resolvedBackHref)
                    <a href="{{ $resolvedBackHref }}" class="ui-button-secondary ui-button-back">
                        <flux:icon name="arrow-left" variant="outline" class="size-4" />
                        {{ $backLabel }}
                    </a>
                @endif

                @isset($actions)
                    {{ $actions }}
                @endisset
            </div>
        @endif
    </div>
</section>
