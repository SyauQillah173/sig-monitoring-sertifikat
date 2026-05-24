<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Mail\CementNotificationTestMail;
use App\Models\NotificationSetting;
use App\Services\Cement\CementCertificateEmailNotificationService;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificationSettingController extends Controller
{
    public function edit(): View
    {
        return view('cement.maintenance.notification-settings.edit', [
            'settings' => $this->settings(),
            'mailStatus' => $this->mailStatus(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'internal_recipient_email' => ['required', 'email', 'max:255'],
            'expiry_warning_days' => ['required', 'string', 'max:255'],
            'send_hour' => ['required', 'integer', 'min:0', 'max:23'],
            'is_email_enabled' => ['required', 'boolean'],
        ]);

        $now = now();

        NotificationSetting::query()->upsert(
            collect($payload)
                ->map(fn (mixed $value, string $key): array => [
                    'key' => $key,
                    'value' => (string) $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->values()
                ->all(),
            ['key'],
            ['value', 'updated_at'],
        );

        app(AuditLogger::class)->log('notification_settings_updated', null, 'Pengaturan email notifikasi diperbarui.', null, $payload);

        return redirect()->route('cement.maintenance.notification-settings.edit')->with('success', 'Pengaturan email notifikasi berhasil disimpan.');
    }

    public function test(Request $request): RedirectResponse
    {
        $email = $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
        ])['test_email'];

        if (config('mail.default') === 'log') {
            return back()
                ->withInput()
                ->with('error', 'Email belum dikirim keluar karena MAIL_MAILER masih "log". Ubah .env ke SMTP lalu jalankan php artisan config:clear.');
        }

        try {
            Mail::to($email)->send(new CementNotificationTestMail);
        } catch (Throwable $throwable) {
            report($throwable);

            return back()
                ->withInput()
                ->with('error', 'Email test gagal dikirim. Periksa MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD/app password, dan koneksi internet server.');
        }

        app(AuditLogger::class)->log('notification_test_email_sent', null, 'Email test notifikasi dikirim.', null, ['email' => $email]);

        return back()->with('success', 'Email test berhasil dikirim ke '.$email.'.');
    }

    public function sendReminders(CementCertificateEmailNotificationService $service): RedirectResponse
    {
        if (config('mail.default') === 'log') {
            return back()->with('error', 'Reminder belum dikirim keluar karena MAIL_MAILER masih "log". Ubah SMTP lalu deploy ulang.');
        }

        try {
            $result = $service->sendDueReminders();
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('error', 'Reminder gagal diproses. Periksa SMTP, koneksi database, dan daftar sertifikat yang jatuh tempo.');
        }

        if ($result['skipped']) {
            return back()->with('error', 'Email otomatis sedang nonaktif. Aktifkan Status Email Otomatis dulu.');
        }

        if ($result['certificates'] === 0) {
            return back()->with('success', 'Reminder diproses, tetapi belum ada sertifikat sistem ISO yang masuk periode Hari Reminder.');
        }

        return back()->with(
            $result['failed'] > 0 ? 'error' : 'success',
            sprintf(
                'Reminder diproses. Penerima: %d, Sertifikat: %d, Terkirim: %d, Gagal: %d.',
                $result['recipients'],
                $result['certificates'],
                $result['sent'],
                $result['failed'],
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    private function settings(): array
    {
        $defaults = [
            'internal_recipient_email' => 'abdullahsyauqillah02@gmail.com',
            'expiry_warning_days' => '90,60,30,7',
            'send_hour' => '7',
            'is_email_enabled' => '1',
        ];

        try {
            return NotificationSetting::query()
                ->pluck('value', 'key')
                ->union($defaults)
                ->all();
        } catch (QueryException) {
            return $defaults;
        }
    }

    /**
     * @return array<string, string|null>
     */
    private function mailStatus(): array
    {
        return [
            'mailer' => (string) config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => (string) config('mail.mailers.smtp.port'),
            'username' => config('mail.mailers.smtp.username') ? 'terisi' : 'belum diisi',
            'password' => config('mail.mailers.smtp.password') ? 'terisi' : 'belum diisi',
            'from' => config('mail.from.address'),
        ];
    }
}
