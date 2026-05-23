<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="ui-auth-shell">
        <div class="ui-auth-centered-shell">
            <div class="ui-auth-centered-wrap">
                <div class="ui-auth-centered-card">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <a href="{{ route('home') }}" class="ui-auth-card-brand" wire:navigate>
                            <span class="ui-auth-card-brand-mark">
                                <x-app-logo-icon class="h-7 w-auto" />
                            </span>

                            <span>
                                <span class="block ui-auth-card-brand-name">{{ config('app.name', 'SIG Monitoring Sertifikat') }}</span>
                            </span>
                        </a>

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
                    </div>

                    <div class="ui-auth-card-divider"></div>

                    <div class="relative z-10">
                        {{ $slot }}
                    </div>
                </div>

                <x-ui.developer-footer class="ui-developer-footer-auth" />
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
