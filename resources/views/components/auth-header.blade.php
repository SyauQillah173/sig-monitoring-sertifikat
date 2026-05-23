@props([
    'title',
    'description',
    'align' => 'center',
])

<div @class([
    'flex w-full flex-col',
    'text-center' => $align === 'center',
    'text-left' => $align === 'left',
])>
    <h1 class="ui-auth-title">{{ $title }}</h1>
    <p class="ui-auth-copy">{{ $description }}</p>
</div>
