<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminHasTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! config('security.enforce_admin_2fa') || ! $user?->hasAppRole(UserRole::Admin)) {
            return $next($request);
        }

        if (filled($user->two_factor_secret) || $this->isTwoFactorSetupRoute($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Admin wajib mengaktifkan two factor authentication.');
        }

        return redirect()
            ->route('security.edit')
            ->with('error', 'Admin wajib mengaktifkan two factor authentication sebelum mengakses data sensitif.');
    }

    private function isTwoFactorSetupRoute(Request $request): bool
    {
        return $request->routeIs('security.edit', 'logout', 'password.confirm', 'password.confirmation')
            || $request->is(
                'settings/security',
                'user/two-factor-authentication*',
                'user/confirm-password*',
                'user/confirmed-password-status',
            );
    }
}
