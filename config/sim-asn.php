<?php

return [
    'base_url' => env('SIMASN_BASE_URL'),
    'api_key' => env('SIMASN_API_KEY'),
    'timeout' => env('SIMASN_TIMEOUT', 30),
    'retry' => env('SIMASN_RETRY', 3),
];