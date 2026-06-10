<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Http\Request;
use Illuminate\View\ViewServiceProvider;

define('LARAVEL_START', microtime(true));

function runVercelProductionMigrations(Application $app): void
{
    $isVercel = getenv('VERCEL') !== false || isset($_ENV['VERCEL'], $_SERVER['VERCEL']);
    $vercelEnvironment = getenv('VERCEL_ENV') ?: ($_ENV['VERCEL_ENV'] ?? $_SERVER['VERCEL_ENV'] ?? null);

    if (! $isVercel || $vercelEnvironment !== 'production') {
        return;
    }

    $commit = preg_replace(
        '/[^A-Za-z0-9_.-]/',
        '',
        (string) (getenv('VERCEL_GIT_COMMIT_SHA') ?: ($_ENV['VERCEL_GIT_COMMIT_SHA'] ?? $_SERVER['VERCEL_GIT_COMMIT_SHA'] ?? 'current'))
    );
    $markerDirectory = sys_get_temp_dir().'/laravel-cache';
    $marker = $markerDirectory.'/migrations-'.$commit.'.done';
    $lock = $markerDirectory.'/migrations.lock';

    if (file_exists($marker)) {
        return;
    }

    if (! is_dir($markerDirectory)) {
        mkdir($markerDirectory, 0777, true);
    }

    $lockHandle = fopen($lock, 'c');

    if ($lockHandle === false) {
        throw new RuntimeException('Unable to open migration lock file.');
    }

    try {
        flock($lockHandle, LOCK_EX);

        if (file_exists($marker)) {
            return;
        }

        $exitCode = $app->make(Kernel::class)->call('migrate', [
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            $output = trim($app->make(Kernel::class)->output());

            throw new RuntimeException('Vercel production migrations failed. '.$output);
        }

        touch($marker);
    } finally {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->afterBootstrapping(LoadConfiguration::class, function (Application $app): void {
    $app->register(FilesystemServiceProvider::class, force: true);
    $app->register(ViewServiceProvider::class, force: true);
});

runVercelProductionMigrations($app);

$app->handleRequest(Request::capture());
