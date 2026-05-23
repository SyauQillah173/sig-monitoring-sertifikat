<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

class ThrottleSensitiveAuthRequests
{
    public function __construct(
        private readonly ThrottleRequests $throttle,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('password.email', 'password.code.verify', 'password.update')) {
            return $next($request);
        }

        return $this->throttle->handle($request, $next, 'password-reset');
    }
}
