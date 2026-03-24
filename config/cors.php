<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        env('FRONTEND_URL', 'http://localhost:3000'),
        // Expo development URLs
        'exp://localhost:8081',
        'exp://127.0.0.1:8081',
        'exp://192.168.0.0/16',
        'exp://10.0.0.0/8',
        // Allow local network IPs for physical device testing
        env('MOBILE_APP_URL', null),
    ],

    'allowed_origins_patterns' => [
        // Allow Expo development server on any local IP
        '#^exp://192\.168\.\d+\.\d+:\d+$#',
        '#^exp://10\.\d+\.\d+\.\d+:\d+$#',
        // Allow local network IPs for mobile app testing
        '#^http://192\.168\.\d+\.\d+:\d+$#',
        '#^http://10\.\d+\.\d+\.\d+:\d+$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
