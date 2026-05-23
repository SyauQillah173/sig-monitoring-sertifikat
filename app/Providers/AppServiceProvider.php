<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SystemNavigationService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthMail();
        $this->configureRateLimits();
        $this->shareLayoutData();
        $this->registerAuditEvents();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Gate::before(function (User $user) {
            return $user->hasAppRole(UserRole::Admin) ? true : null;
        });

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        if (app()->isProduction() || filter_var(env('APP_FORCE_HTTPS', false), FILTER_VALIDATE_BOOL)) {
            URL::forceScheme('https');
        }

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configureAuthMail(): void
    {
        ResetPassword::toMailUsing(function (User $user, string $token): MailMessage {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ], false));

            return (new MailMessage)
                ->subject('[SIG] Permintaan Reset Password')
                ->greeting('Halo '.$user->name.',')
                ->line('Kami menerima permintaan untuk mengganti password akun SIG Monitoring Sertifikat Anda.')
                ->action('Ganti Password', $url)
                ->line('Link ini hanya berlaku sementara. Abaikan email ini jika Anda tidak meminta reset password.')
                ->salutation('Hormat kami, SIG Monitoring Sertifikat');
        });
    }

    protected function shareLayoutData(): void
    {
        View::composer('layouts.app.sidebar', function ($view): void {
            if (! auth()->check()) {
                $view->with('layoutNotifications', [
                    'unreadCount' => 0,
                ]);

                return;
            }

            $view->with('layoutNotifications', [
                'unreadCount' => auth()->user()->unreadSystemNotifications()->count(),
            ]);
            $view->with('navigationGroups', app(SystemNavigationService::class)->groupsForUser(auth()->user()));
        });
    }

    protected function configureRateLimits(): void
    {
        RateLimiter::for('sensitive-download', fn (Request $request) => [
            Limit::perMinute(30)->by(($request->user()?->id ?: $request->ip()).'|download'),
        ]);

        RateLimiter::for('sensitive-import', fn (Request $request) => [
            Limit::perMinute(6)->by(($request->user()?->id ?: $request->ip()).'|import'),
        ]);

        RateLimiter::for('sensitive-export', fn (Request $request) => [
            Limit::perMinute(12)->by(($request->user()?->id ?: $request->ip()).'|export'),
        ]);

        RateLimiter::for('password-reset', fn (Request $request) => [
            Limit::perMinute(3)->by(strtolower((string) $request->input('email')).'|'.$request->ip().'|password-reset'),
        ]);
    }

    protected function registerAuditEvents(): void
    {
        Event::listen(Failed::class, function (Failed $event): void {
            app(AuditLogger::class)->log('login_failed', $event->user, 'Percobaan login gagal.', null, [
                'email' => $event->credentials['email'] ?? null,
            ]);
        });

        Event::listen(Login::class, function (Login $event): void {
            app(AuditLogger::class)->log('login', $event->user, 'Pengguna login.');
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user instanceof User) {
                app(AuditLogger::class)->log('logout', $event->user, 'Pengguna logout.');
            }
        });
    }
}
