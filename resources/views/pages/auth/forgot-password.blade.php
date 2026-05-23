<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Reset password')" :description="__('Masukkan email login yang sudah didaftarkan admin. Sistem akan mengirim kode 6 digit ke email tersebut.')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        @if (session('error'))
            <div class="ui-auth-error-summary">
                <p class="ui-auth-error-title">{{ session('error') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="ui-auth-form">
            @csrf

            <flux:input
                name="email"
                :label="__('Email login')"
                type="email"
                required
                autofocus
                :value="old('email')"
                placeholder="nama@gmail.com"
            />

            <flux:button variant="primary" type="submit" class="ui-login-submit" data-test="send-password-reset-code-button">
                {{ __('Kirim Kode Reset') }}
            </flux:button>
        </form>

        <div class="ui-panel-muted p-4 text-center text-sm text-slate-600">
            <span>{{ __('Sudah ingat password?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Kembali login') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
