<?php

$isVercel = getenv('VERCEL') !== false;
$vercelEnvironment = getenv('VERCEL_ENV') ?: null;

if ($isVercel && $vercelEnvironment !== 'production') {
    echo "Skipping database migrations for Vercel {$vercelEnvironment} deployment.".PHP_EOL;

    exit(0);
}

echo 'Running database migrations before Vercel build...'.PHP_EOL;

$command = escapeshellarg(PHP_BINARY).' artisan migrate --force';
passthru($command, $exitCode);

if ($exitCode !== 0) {
    echo 'Database migrations failed. Aborting Vercel build.'.PHP_EOL;
}

exit($exitCode);
