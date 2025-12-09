<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    // Paths that should be CORS-enabled
    'paths' => [
        'api/*',
        'auth/*',
        'sanctum/csrf-cookie',
        'logout',
        'login',
    ],

    // Allow all HTTP methods
    'allowed_methods' => ['*'],

    // ✅ MUST BE SPECIFIC when using credentials
    'allowed_origins' => env('CORS_ALLOWED_ORIGINS') 
        ? array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS')))
        : [
            'http://localhost:3002',
            'http://127.0.0.1:3002',
            'http://localhost:3000',
            'http://127.0.0.1:3000',
            'https://localhost:3002',
            'https://127.0.0.1:3002',
            'https://localhost:3000',
            'https://127.0.0.1:3000',
            'http://209.126.86.149:3002',
            'https://209.126.86.149:3002',
            'https://kirsten-vaulted-margarita.ngrok-free.dev'
        ],

    // Do NOT use patterns when credentials are enabled
    'allowed_origins_patterns' => [],

    // Allow all headers
    'allowed_headers' => ['*'],

    // Expose Set-Cookie header so browser can receive cookies
    'exposed_headers' => ['Set-Cookie', 'Authorization'],

    // Disable caching of preflight
    'max_age' => 0,

    // ✅ REQUIRED for axios withCredentials: true
    'supports_credentials' => true,

];

