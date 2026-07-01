<?php

try {
    if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL'])) {
        $cacheDirectory = '/tmp/laravel-cache';
        $viewDirectory = $cacheDirectory.'/views';
        $cacheDataDirectory = $cacheDirectory.'/data';

        if (! is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0777, true);
        }

        if (! is_dir($viewDirectory)) {
            mkdir($viewDirectory, 0777, true);
        }

        if (! is_dir($cacheDataDirectory)) {
            mkdir($cacheDataDirectory, 0777, true);
        }

        $databaseHost = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? getenv('DB_HOST') ?: '';
        $configuredSslCa = $_ENV['MYSQL_ATTR_SSL_CA'] ?? $_SERVER['MYSQL_ATTR_SSL_CA'] ?? getenv('MYSQL_ATTR_SSL_CA') ?: '';

        if (str_contains($databaseHost, 'tidbcloud.com') && $configuredSslCa === '') {
            $tidbCaPath = realpath(__DIR__.'/../certs/tidb-ca.pem') ?: __DIR__.'/../certs/tidb-ca.pem';
            $_ENV['MYSQL_ATTR_SSL_CA'] = $tidbCaPath;
            $_SERVER['MYSQL_ATTR_SSL_CA'] = $tidbCaPath;
            putenv('MYSQL_ATTR_SSL_CA='.$tidbCaPath);
        }

        if (str_contains($databaseHost, 'aivencloud.com') && str_contains($configuredSslCa, 'tidb-ca.pem')) {
            unset($_ENV['MYSQL_ATTR_SSL_CA'], $_SERVER['MYSQL_ATTR_SSL_CA']);
            putenv('MYSQL_ATTR_SSL_CA');
        }

        $runtimeValues = [
            'APP_PACKAGES_CACHE' => $cacheDirectory.'/packages.php',
            'APP_SERVICES_CACHE' => $cacheDirectory.'/services.php',
            'APP_CONFIG_CACHE' => $cacheDirectory.'/config.php',
            'APP_ROUTES_CACHE' => $cacheDirectory.'/routes.php',
            'APP_EVENTS_CACHE' => $cacheDirectory.'/events.php',
            'VIEW_COMPILED_PATH' => $viewDirectory,
            'SESSION_DRIVER' => 'cookie',
            'SESSION_PATH' => '/',
            'CACHE_STORE' => 'file',
            'CACHE_PATH' => $cacheDataDirectory,
            'CACHE_LOCK_PATH' => $cacheDataDirectory,
            'EXCEL_TEMP_PATH' => $cacheDirectory.'/laravel-excel',
            'QUEUE_CONNECTION' => 'sync',
        ];

        foreach ($runtimeValues as $key => $value) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key.'='.$value);
        }

        if (($_ENV['SESSION_DOMAIN'] ?? $_SERVER['SESSION_DOMAIN'] ?? null) === 'null') {
            unset($_ENV['SESSION_DOMAIN'], $_SERVER['SESSION_DOMAIN']);
            putenv('SESSION_DOMAIN');
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
