<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ShrtLnk URL shortener API
    |--------------------------------------------------------------------------
    | @see https://shrtnlnk.com/api-docs#create-link-api
    */
    'enabled' => filter_var(env('SHRTLNK_ENABLED', true), FILTER_VALIDATE_BOOL),

    'base_url' => rtrim(env('SHRTLNK_BASE_URL', 'https://shrtnlnk.com'), '/'),

    'source' => env('SHRTLNK_SOURCE', 'engagyo'),

    'timeout' => (int) env('SHRTLNK_TIMEOUT', 15),

    /*
    | If the API is unreachable, create links on the Engagyo domain instead.
    */
    'fallback_local' => filter_var(env('SHRTLNK_FALLBACK_LOCAL', false), FILTER_VALIDATE_BOOL),

];
