@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand :name="config('app.name', 'SIG Monitoring Sertifikat')" {{ $attributes }}>
        <x-slot name="logo" class="flex h-10 min-w-[5rem] items-center justify-center rounded-2xl bg-white px-3 shadow-[0_14px_30px_-18px_rgba(15,23,42,0.65)]">
            <x-app-logo-icon class="h-6 w-auto" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="config('app.name', 'SIG Monitoring Sertifikat')" {{ $attributes }}>
        <x-slot name="logo" class="flex h-10 min-w-[5rem] items-center justify-center rounded-2xl bg-white px-3 shadow-[0_14px_30px_-18px_rgba(15,23,42,0.65)]">
            <x-app-logo-icon class="h-6 w-auto" />
        </x-slot>
    </flux:brand>
@endif
