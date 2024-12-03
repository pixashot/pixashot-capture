<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pixashot Cloud Run Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Pixashot Cloud Run service
    |
    */

    'endpoint' => env('CLOUD_RUN_ENDPOINT', 'https://pixashot-544054581516.us-central1.run.app'),
    'auth_token' => env('CLOUD_RUN_AUTH_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for captured screenshots
    |
    */

    'cache' => [
        'max_age' => env('PIXASHOT_CACHE_MAX_AGE', 60 * 60 * 24 * 14), // 14 days
        'stale_while_revalidate' => env('PIXASHOT_CACHE_SWR', 60 * 60 * 24 * 7), // 7 days
    ],
];
