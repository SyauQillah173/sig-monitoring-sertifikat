<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureUserCanAccessRoute;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ThrottleSensitiveAuthRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AddSecurityHeaders::class);
        $middleware->web(append: [
            ThrottleSensitiveAuthRequests::class,
            EnsureUserIsActive::class,
            EnsureUserCanAccessRoute::class,
        ]);

        $middleware->alias([
            'app.role' => EnsureUserHasRole::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
