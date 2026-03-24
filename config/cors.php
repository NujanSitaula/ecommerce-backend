<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [],
    'allowed_origins_patterns' => [
        '#^https?://[a-z0-9-]+\.35\.225\.205\.38\.sslip\.io$#',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];