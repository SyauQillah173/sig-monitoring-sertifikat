<?php

namespace App\Http\Middleware;

use App\Services\SystemNavigationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccessRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();

        if (! $user || app(SystemNavigationService::class)->canAccessRoute($user, $routeName, $request)) {
            return $next($request);
        }

        abort(403);
    }
}
