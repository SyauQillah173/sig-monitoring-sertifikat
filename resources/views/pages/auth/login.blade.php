<x-layouts::auth :title="__('Log in')">
    <div class="ui-login-page">
        <aside class="ui-login-showcase">
            <div>
                <p class="ui-login-showcase-kicker">SIG Monitoring Sertifikat</p>
                <h1 class="ui-login-showcase-title">Masuk ke workspace monitoring yang terpusat.</h1>
                <p class="ui-login-showcase-copy">
                    Pantau masa berlaku sertifikat, dokumen pendukung, notifikasi internal, dan laporan operasional dalam satu sistem.
                </p>
            </div>

            <div class="ui-login-showcase-grid">
                <div class="ui-login-showcase-card">
                    <span class="ui-login-showcase-dot bg-emerald-300"></span>
                    <p>Monitoring aktif</p>
                </div>
                <div class="ui-login-showcase-card">
                    <span class="ui-login-showcase-dot bg-amber-300"></span>
                    <p>Alert masa berlaku</p>
                </div>
                <div class="ui-login-showcase-card">
                    <span class="ui-login-showcase-dot bg-sky-300"></span>
                    <p>Laporan siap pakai</p>
                </div>
            </div>
        </aside>

        <section class="ui-login-form-shell">
            <div class="space-y-3">
                <x-auth-header
                    align="left"
                    :title="__('Masuk ke sistem monitoring')"
                    :description="__('Gunakan akun sesuai hak akses Anda untuk mengakses monitoring sertifikat, dokumen terpusat, dan ringkasan operasional.')"
                />
            </div>

            <x-auth-session-status :status="session('status')" />

            @if ($errors->any())
                <div class="ui-auth-error-summary">
                    <p class="ui-auth-error-title">Login Tidak Berhasil</p>
                    <ul class="mt-3 space-y-1.5 text-sm leading-6">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="ui-login-panel">
                <form method="POST" action="{{ route('login.store') }}" class="ui-auth-form">
                    @csrf

                    <div class="ui-login-field">
                        <label class="ui-login-field-label" for="email">Alamat Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            class="ui-input"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="nama@instansi.go.id"
                        >
                    </div>

                    <div class="ui-login-field" x-data="{ showPassword: false }">
                        <label class="ui-login-field-label" for="password">Password</label>
                        <div class="ui-password-input-wrap">
                            <input
                                id="password"
                                name="password"
                                x-bind:type="showPassword ? 'text' : 'password'"
                                class="ui-input ui-password-input"
                                required
                                autocomplete="current-password"
                                placeholder="Masukkan password Anda"
                            >

                            <button
                                type="button"
                                class="ui-password-toggle"
                                x-on:click="showPassword = ! showPassword"
                                x-bind:aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                                x-bind:aria-pressed="showPassword.toString()"
                            >
                                <flux:icon name="eye" variant="outline" class="size-5" x-show="! showPassword" />
                                <flux:icon name="eye-slash" variant="outline" class="size-5" x-show="showPassword" x-cloak />
                            </button>
                        </div>
                    </div>

                    <div class="ui-login-utility">
                        <label for="remember" class="ui-check-row">
                            <input
                                id="remember"
                                name="remember"
                                type="checkbox"
                                value="1"
                                @checked(old('remember'))
                                class="ui-check-input"
                            >
                            <span>{{ __('Ingat saya') }}</span>
                        </label>

                        @if (Route::has('password.request'))
                            <flux:link class="ui-login-link" :href="route('password.request')" wire:navigate>
                                {{ __('Lupa password?') }}
                            </flux:link>
                        @endif
                    </div>

                    <div class="space-y-3">
                        <flux:button variant="primary" type="submit" class="ui-login-submit" data-test="login-button">
                            <span>{{ __('Masuk ke Sistem') }}</span>
                            <flux:icon name="arrow-right" variant="mini" class="size-4" />
                        </flux:button>
                    </div>
                </form>
            </div>

            <div class="ui-auth-card-note">
                Akses dilindungi dan aktivitas login dicatat untuk kebutuhan monitoring internal.
            </div>
        </section>
    </div>
</x-layouts::auth>
