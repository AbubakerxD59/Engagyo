<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ShrtLnk URL shortener API
    |--------------------------------------------------------------------------
    | @see https://shrtnlnk.com/api-docs
    */
    'enabled' => filter_var(env('SHRTLNK_ENABLED', true), FILTER_VALIDATE_BOOL),

    'base_url' => rtrim(env('SHRTLNK_BASE_URL', 'https://shrtnlnk.com'), '/'),

    'source' => env('SHRTLNK_SOURCE', 'engagyo'),

    'timeout' => (int) env('SHRTLNK_TIMEOUT', 15),

    /*
    | If the API is unreachable, create links on the Engagyo domain instead.
    */
    'fallback_local' => filter_var(env('SHRTLNK_FALLBACK_LOCAL', false), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | Click sync (hourly cron: shrtlnk:sync-clicks)
    |--------------------------------------------------------------------------
    | Uses GET /api/links/{code} — https://shrtnlnk.com/api-docs#get-link-api
    */
    'sync_clicks_enabled' => filter_var(env('SHRTLNK_SYNC_CLICKS_ENABLED', true), FILTER_VALIDATE_BOOL),

    'sync_chunk' => (int) env('SHRTLNK_SYNC_CHUNK', 100),

];
