<x-layouts::auth :title="__('Kode reset password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Masukkan kode reset')" :description="__('Cek inbox email Anda lalu masukkan kode 6 digit yang dikirim oleh SIG Monitoring Sertifikat.')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        @if (session('error'))
            <div class="ui-auth-error-summary">
                <p class="ui-auth-error-title">{{ session('error') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.code.verify') }}" class="ui-auth-code-form" data-reset-code-form>
            @csrf

            <div class="space-y-2">
                <label class="ui-label" for="email">Email login</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $email) }}"
                    class="ui-input"
                    autocomplete="email"
                    required
                >
                @error('email')
                    <p class="ui-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label class="ui-label" for="code">Kode 6 digit dari email</label>
                <input
                    id="code"
                    name="code"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    value="{{ old('code') }}"
                    class="ui-input ui-reset-code-input"
                    placeholder="000000"
                    autocomplete="one-time-code"
                    autofocus
                    required
                    data-reset-code-input
                >
                <p class="ui-input-hint">Setelah 6 digit terisi, sistem akan langsung mencoba verifikasi kode.</p>
                @error('code')
                    <p class="ui-field-error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="ui-button-primary w-full" data-test="verify-password-reset-code-button">
                Verifikasi Kode
            </button>
        </form>

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <input type="hidden" name="email" value="{{ old('email', $email) }}">
            <button class="ui-button-secondary w-full" type="submit">Kirim Ulang Kode</button>
        </form>
    </div>

    <script>
        (() => {
            const form = document.querySelector('[data-reset-code-form]');
            const input = document.querySelector('[data-reset-code-input]');
            if (!form || !input) return;

            let submitted = false;
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(0, 6);

                if (!submitted && input.value.length === 6) {
                    submitted = true;
                    form.requestSubmit();
                }
            });
        })();
    </script>
</x-layouts::auth>
