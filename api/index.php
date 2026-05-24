<?php

try {
    if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL'])) {
        $cacheDirectory = '/tmp/laravel-cache';
        $viewDirectory = $cacheDirectory.'/views';

        if (! is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0777, true);
        }

        if (! is_dir($viewDirectory)) {
            mkdir($viewDirectory, 0777, true);
        }

        $cachePaths = [
            'APP_PACKAGES_CACHE' => $cacheDirectory.'/packages.php',
            'APP_SERVICES_CACHE' => $cacheDirectory.'/services.php',
            'APP_CONFIG_CACHE' => $cacheDirectory.'/config.php',
            'APP_ROUTES_CACHE' => $cacheDirectory.'/routes.php',
            'APP_EVENTS_CACHE' => $cacheDirectory.'/events.php',
            'VIEW_COMPILED_PATH' => $viewDirectory,
        ];

        foreach ($cachePaths as $key => $value) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key.'='.$value);
        }
    }

    require __DIR__.'/../public/index.php';
} catch (Throwable $exception) {
    error_log((string) $exception);

    http_response_code(500);

    if (filter_var($_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL)) {
        header('Content-Type: text/plain; charset=UTF-8');

        echo $exception::class.PHP_EOL;
        echo $exception->getMessage().PHP_EOL.PHP_EOL;
        echo $exception->getTraceAsString();

        return;
    }

    echo 'Server error.';
}
