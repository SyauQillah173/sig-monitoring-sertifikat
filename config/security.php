<?php

return [
    'enforce_admin_2fa' => env('SECURITY_ENFORCE_ADMIN_2FA', false),

    'headers' => [
        'content_security_policy' => env(
            'SECURITY_CSP',
            "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.bunny.net; font-src 'self' https://fonts.bunny.net data:; img-src 'self' data: blob:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'"
        ),
    ],
];
