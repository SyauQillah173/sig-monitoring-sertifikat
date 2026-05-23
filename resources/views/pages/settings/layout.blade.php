<div class="ui-settings-layout">
    <div class="ui-settings-nav">
        <flux:navlist aria-label="{{ __('Settings') }}">
            <flux:navlist.item :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item :href="route('security.edit')" wire:navigate>{{ __('Security') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <div class="ui-settings-panel">
        <flux:heading class="ui-settings-panel-title">{{ $heading ?? '' }}</flux:heading>
        <flux:subheading class="ui-settings-panel-subtitle">{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-6 w-full max-w-2xl">
            {{ $slot }}
        </div>
    </div>
</div>
