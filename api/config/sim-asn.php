<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SIM-ASN API Configuration
    |--------------------------------------------------------------------------
    */
    'base_url'  => env('SIM_ASN_URL', 'https://api.sim-asn.bkpsdm.karawangkab.go.id'),
    'api_key'   => env('SIM_ASN_API_KEY'),
    'timeout'   => env('SIM_ASN_TIMEOUT', 30),
    'retry'     => env('SIMASN_RETRY', 3),

    'url'          => env('SIM_ASN_URL', 'https://api.sim-asn.bkpsdm.karawangkab.go.id'),
    'frontend_url' => env('SIM_ASN_FRONTEND_URL', 'https://sim-asn.bkpsdm.karawangkab.go.id'),
    'client_id'     => env('SIM_ASN_CLIENT_ID'),
    'client_secret' => env('SIM_ASN_CLIENT_SECRET'),
    'app_key'       => env('SIM_ASN_APP_KEY', null),
    'profile_url'   => env('SIM_ASN_PROFILE_URL', '/private/profile'),

    // OAuth user service
    'user_callback'       => env('SIM_ASN_USER_CALLBACK', env('APP_URL').'/callback/sim-asn'),
    'user_scope'          => env('SIM_ASN_USER_SCOPE', '*'),
    'user_redirect_state' => [
        'login'    => env('CLIENT_LOGIN_PAGE', env('FRONTEND_URL').'/login'),
        'register' => env('CLIENT_REGISTER_PAGE', env('FRONTEND_URL').'/register'),
        'profile'  => env('CLIENT_PROFILE_PAGE', env('FRONTEND_URL').'/profile'),
    ],

    // OAuth app service
    // Available storage is `file` or `cache` with default `cache`
    'app_token_storage'   => env('SIM_ASN_APP_TOKEN_STORAGE', 'cache'),
    'app_token_cache_key' => env('SIM_ASN_APP_TOKEN_CACHE_KEY', 'sim_asn_access_token'),
    'app_token_path'      => env('SIM_ASN_APP_TOKEN_PATH', 'app/app_access_token.json'),
    'app_scope'           => env('SIM_ASN_APP_SCOPE', '*'),

    /*
    |--------------------------------------------------------------------------
    | SIM-ASN Routes
    |--------------------------------------------------------------------------
    */
    'route_proxy_auto'       => env('SIM_ASN_ROUTE_PROXY_AUTO', true),
    'route_proxy_prefix'     => env('SIM_ASN_ROUTE_PROXY_PREFIX', 'sim-asn'),
    'route_proxy_middleware' => ['auth'],

    /*
    |--------------------------------------------------------------------------
    | Field Casting
    |--------------------------------------------------------------------------
    */
    'cast_fields' => [],
];
