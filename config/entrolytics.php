<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Entrolytics Website ID
    |--------------------------------------------------------------------------
    |
    | Your Entrolytics website ID. Get this from your Entrolytics dashboard.
    |
    */
    'website_id' => env('ENTROLYTICS_WEBSITE_ID'),

    /*
    |--------------------------------------------------------------------------
    | Entrolytics API Key
    |--------------------------------------------------------------------------
    |
    | Your Entrolytics API key for server-side tracking.
    |
    */
    'api_key' => env('ENTROLYTICS_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Entrolytics Host
    |--------------------------------------------------------------------------
    |
    | The Entrolytics API host. Change this if you're self-hosting.
    |
    */
    'host' => env('ENTROLYTICS_HOST', 'https://entrolytics.click'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The HTTP request timeout in seconds.
    |
    */
    'timeout' => env('ENTROLYTICS_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Excluded Paths
    |--------------------------------------------------------------------------
    |
    | Paths to exclude from automatic page view tracking.
    | Uses Laravel's request->is() pattern matching.
    |
    */
    'excluded_paths' => [
        'api/*',
        'telescope/*',
        'horizon/*',
        '_debugbar/*',
        'livewire/*',
    ],
];
