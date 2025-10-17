<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Response Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for API response optimization and error handling
    |
    */

    'optimization' => [
        'enable_gzip' => env('API_ENABLE_GZIP', true),
        'gzip_threshold' => env('API_GZIP_THRESHOLD', 1024), // bytes
        'max_response_size' => env('API_MAX_RESPONSE_SIZE', '10M'),
        'timeout' => env('API_TIMEOUT', 60), // seconds
    ],

    'headers' => [
        'default' => [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Cache-Control' => 'no-cache, private',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
        ],
    ],

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

    'error_handling' => [
        'log_level' => env('API_LOG_LEVEL', 'error'),
        'include_trace' => env('API_INCLUDE_TRACE', false),
        'sanitize_errors' => env('API_SANITIZE_ERRORS', true),
    ],
];
