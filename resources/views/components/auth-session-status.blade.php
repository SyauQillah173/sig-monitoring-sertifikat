@props([
    'status',
])

@if ($status)
    <div {{ $attributes->merge(['class' => 'ui-auth-status']) }}>
        {{ $status }}
    </div>
@endif
