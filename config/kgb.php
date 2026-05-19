<?php

return [
    'kgb' => [
        'default_per_page' => env('KGB_PER_PAGE', 20),
        'max_per_page' => env('KGB_MAX_PER_PAGE', 100),
        'draft_retention_days' => env('KGB_DRAFT_RETENTION_DAYS', 30),
    ],
    'sim_asn' => [
        'base_url' => env('SIMASN_BASE_URL'),
        'api_key' => env('SIMASN_API_KEY'),
        'timeout' => env('SIMASN_TIMEOUT', 30),
    ],
    'storage' => [
        'disk' => env('KGB_STORAGE_DISK', 's3'),
        'sk_path' => env('KGB_SK_PATH', 'sk-kgb'),
    ],
];