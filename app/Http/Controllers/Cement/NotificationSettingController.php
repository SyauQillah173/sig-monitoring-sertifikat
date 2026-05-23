<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Mail\CementNotificationTestMail;
use App\Models\NotificationSetting;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
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

        foreach ($payload as $key => $value) {
            NotificationSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value],
            );
        }

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

        return NotificationSetting::query()
            ->pluck('value', 'key')
            ->union($defaults)
            ->all();
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
