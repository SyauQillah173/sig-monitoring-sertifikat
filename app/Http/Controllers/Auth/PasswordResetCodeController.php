<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Auth\PasswordResetCodeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Throwable;

class PasswordResetCodeController extends Controller
{
    public function __construct(
        private readonly PasswordResetCodeService $resetCodeService,
    ) {}

    public function request(): View
    {
        return view('pages::auth.forgot-password');
    }

    public function send(Request $request): RedirectResponse
    {
        $email = $this->normalizedEmail($request->validate([
            'email' => ['required', 'email', 'max:255'],
        ])['email']);

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Email ini belum terdaftar oleh admin.']);
        }

        try {
            $this->resetCodeService->send($user);
        } catch (Throwable $throwable) {
            report($throwable);

            return back()
                ->withInput()
                ->with('error', 'Kode reset password gagal dikirim. Periksa SMTP dan MAIL_PASSWORD/app password di .env.');
        }

        app(AuditLogger::class)->log('password_reset_code_sent', $user, 'Kode reset password dikirim.', null, [
            'email' => $user->email,
        ]);

        return redirect()
            ->route('password.code', ['email' => $user->email])
            ->with('status', 'Kode reset password sudah dikirim ke '.$user->email.'.');
    }

    public function code(Request $request): View
    {
        return view('pages::auth.password-reset-code', [
            'email' => $this->normalizedEmail((string) $request->query('email')),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:6'],
        ]);
        $email = $this->normalizedEmail($payload['email']);

        if (! $this->resetCodeService->verify($email, $payload['code'])) {
            return back()
                ->withInput(['email' => $email])
                ->withErrors(['code' => 'Kode reset password salah atau sudah kedaluwarsa.']);
        }

        $request->session()->put('password_reset_verified_email', $email);
        $request->session()->put('password_reset_verified_at', now()->timestamp);

        return redirect()
            ->route('password.reset', ['email' => $email])
            ->with('status', 'Kode berhasil diverifikasi. Silakan buat password baru.');
    }

    public function reset(Request $request): View|RedirectResponse
    {
        $email = $this->normalizedEmail((string) $request->query('email'));

        if (! $this->isVerifiedSession($request, $email)) {
            return redirect()
                ->route('password.code', ['email' => $email])
                ->with('error', 'Masukkan kode reset password terlebih dahulu.');
        }

        return view('pages::auth.reset-password', [
            'email' => $email,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ]);
        $email = $this->normalizedEmail($payload['email']);

        if (! $this->isVerifiedSession($request, $email)) {
            return redirect()
                ->route('password.code', ['email' => $email])
                ->with('error', 'Kode reset password belum diverifikasi atau sesi sudah kedaluwarsa.');
        }

        $user = User::query()->where('email', $email)->firstOrFail();
        $user->forceFill([
            'password' => Hash::make($payload['password']),
        ])->save();

        $this->resetCodeService->delete($email);
        $request->session()->forget(['password_reset_verified_email', 'password_reset_verified_at']);

        app(AuditLogger::class)->log('password_reset_completed', $user, 'Password user berhasil diganti memakai kode reset.', null, [
            'email' => $user->email,
        ]);

        return redirect()
            ->route('login')
            ->with('status', 'Password berhasil diganti. Silakan login dengan password baru.');
    }

    private function normalizedEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function isVerifiedSession(Request $request, string $email): bool
    {
        $verifiedAt = (int) $request->session()->get('password_reset_verified_at');

        return $request->session()->get('password_reset_verified_email') === $email
            && $verifiedAt > 0
            && now()->subMinutes($this->resetCodeService->expiresInMinutes())->timestamp <= $verifiedAt;
    }
}
