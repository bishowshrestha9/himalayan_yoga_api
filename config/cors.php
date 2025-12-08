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
        'sanctum/csrf-cookie',
    ],

    // Allow all HTTP methods
    'allowed_methods' => ['*'],

    // ✅ MUST BE SPECIFIC when using credentials
    'allowed_origins' => [
        'http://localhost:3002',
        'http://127.0.0.1:3002',
        'http://209.126.86.149:3002',
        'https://209.126.86.149:3002',
    ],

    // Do NOT use patterns when credentials are enabled
    'allowed_origins_patterns' => [],

    // Allow all headers
    'allowed_headers' => ['*'],

    // No special exposed headers needed
    'exposed_headers' => [],

    // Disable caching of preflight
    'max_age' => 0,

    // ✅ REQUIRED for axios withCredentials: true
    'supports_credentials' => true,

];

