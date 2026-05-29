<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Paths: api/* for all API routes, auth/* and callback/* for OAuth web routes.
    | Supports credentials so the frontend can send Authorization headers.
    |
    */

    'paths' => [
        'api/*',
        'auth/*',
        'callback/*',
    ],

    'allowed_methods' => ['*'],

    // Allow both local dev (localhost:3000) and production frontend
    'allowed_origins' => [
        env('APP_FRONTEND_URL', 'http://localhost:3000'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    // Expose Authorization header so client can read it if needed
    'exposed_headers' => ['Authorization'],

    'max_age' => 0,

    // Required for credential-based auth (bearer tokens)
    'supports_credentials' => true,

];
