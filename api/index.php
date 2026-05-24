<?php

try {
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
