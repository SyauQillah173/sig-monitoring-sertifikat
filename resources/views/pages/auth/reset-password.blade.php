<x-layouts::auth :title="__('Reset password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Buat password baru')" :description="__('Kode reset sudah benar. Silakan buat password baru untuk akun Anda.')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="ui-auth-form">
            @csrf

            <flux:input
                name="email"
                value="{{ old('email', $email) }}"
                :label="__('Email login')"
                type="email"
                required
                readonly
                autocomplete="email"
            />

            <flux:input
                name="password"
                :label="__('Password baru')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password baru')"
                viewable
            />

            <flux:input
                name="password_confirmation"
                :label="__('Konfirmasi password baru')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Konfirmasi password baru')"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="ui-login-submit" data-test="reset-password-button">
                    {{ __('Simpan Password Baru') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth>
