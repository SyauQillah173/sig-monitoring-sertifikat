<x-layouts::app :title="'Pengaturan Email & Notifikasi'">
    <div class="ui-page">
        <x-ui.page-header
            eyebrow="Keamanan & Email"
            title="Pengaturan Email & Notifikasi"
            description="Atur penerima, jadwal reminder, dan uji konfigurasi SMTP. Password SMTP tetap dibaca dari file .env agar tidak masuk database."
        />

        @include('admin.master-data.partials.flash-messages')

        <div class="grid gap-5 xl:grid-cols-[1fr_0.7fr]">
            <div class="space-y-5">
                <form method="POST" action="{{ route('cement.maintenance.notification-settings.update') }}" class="ui-form-panel">
                    @csrf
                    @method('PUT')

                    @include('cement.maintenance.certificates.shared-errors')

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="ui-label" for="internal_recipient_email">Email Internal Default</label>
                            <input id="internal_recipient_email" name="internal_recipient_email" type="email" value="{{ old('internal_recipient_email', $settings['internal_recipient_email']) }}" class="ui-input" required>
                        </div>
                        <div class="space-y-2">
                            <label class="ui-label" for="expiry_warning_days">Hari Reminder</label>
                            <input id="expiry_warning_days" name="expiry_warning_days" value="{{ old('expiry_warning_days', $settings['expiry_warning_days']) }}" class="ui-input" placeholder="90,60,30,7" required>
                        </div>
                        <div class="space-y-2">
                            <label class="ui-label" for="send_hour">Jam Kirim</label>
                            <input id="send_hour" name="send_hour" type="number" min="0" max="23" value="{{ old('send_hour', $settings['send_hour']) }}" class="ui-input" required>
                        </div>
                        <div class="space-y-2">
                            <label class="ui-label" for="is_email_enabled">Status Email Otomatis</label>
                            <select id="is_email_enabled" name="is_email_enabled" class="ui-select">
                                <option value="0" @selected(old('is_email_enabled', $settings['is_email_enabled']) === '0')>Nonaktif</option>
                                <option value="1" @selected(old('is_email_enabled', $settings['is_email_enabled']) === '1')>Aktif</option>
                            </select>
                        </div>
                    </div>

                    <p class="ui-input-hint mt-5">Reminder otomatis dikirim ke kontak perusahaan aktif. Untuk email reset password dan notifikasi keluar, pastikan SMTP di .env sudah aktif.</p>

                    <div class="mt-6">
                        <button class="ui-button-primary">Simpan Pengaturan</button>
                    </div>
                </form>

                <div class="ui-table-shell p-6">
                    <h2 class="ui-title-sm">Status SMTP Saat Ini</h2>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <div>
                            <p class="ui-table-row-meta">Mailer</p>
                            <p class="ui-table-row-title">{{ $mailStatus['mailer'] }}</p>
                        </div>
                        <div>
                            <p class="ui-table-row-meta">Host SMTP</p>
                            <p class="ui-table-row-title">{{ $mailStatus['host'] }}:{{ $mailStatus['port'] }}</p>
                        </div>
                        <div>
                            <p class="ui-table-row-meta">Username</p>
                            <p class="ui-table-row-title">{{ $mailStatus['username'] }}</p>
                        </div>
                        <div>
                            <p class="ui-table-row-meta">Password/App Password</p>
                            <p class="ui-table-row-title">{{ $mailStatus['password'] }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="ui-table-row-meta">From Address</p>
                            <p class="ui-table-row-title">{{ $mailStatus['from'] }}</p>
                        </div>
                    </div>
                    <p class="ui-input-hint mt-5">Kalau mailer masih <strong>log</strong>, email hanya masuk ke file log dan tidak akan sampai ke inbox.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('cement.maintenance.notification-settings.test') }}" class="ui-form-panel">
                @csrf

                <h2 class="ui-title-sm">Test Email</h2>
                <p class="ui-copy mt-2">Kirim email test untuk memastikan SMTP aktif sebelum dipakai reset password dan reminder sertifikat.</p>

                <div class="mt-5 space-y-2">
                    <label class="ui-label" for="test_email">Email Tujuan</label>
                    <input id="test_email" name="test_email" type="email" value="{{ old('test_email', $settings['internal_recipient_email']) }}" class="ui-input" required>
                </div>

                <div class="mt-6">
                    <button class="ui-button-secondary">Kirim Test</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
