<x-layouts::auth :title="__('Konfirmasi keamanan akun')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Konfirmasi keamanan akun')"
            :description="__('Masukkan password akun untuk membuka pengaturan keamanan seperti 2FA, recovery code, dan perubahan password.')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Password')"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full !rounded-full !bg-slate-950 !py-3.5 !text-sm !font-semibold hover:!bg-teal-700" data-test="confirm-password-button">
                {{ __('Lanjutkan') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
