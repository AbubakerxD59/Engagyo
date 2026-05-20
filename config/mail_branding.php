<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand identity (transactional emails)
    |--------------------------------------------------------------------------
    */
    'app_name' => env('MAIL_BRAND_NAME', env('APP_NAME', 'Engagyo')),
    'tagline' => env('MAIL_BRAND_TAGLINE', 'Schedule smarter. Grow faster.'),
    'support_email' => env('MAIL_SUPPORT_ADDRESS', env('MAIL_FROM_ADDRESS', 'support@example.com')),
    'logo_url' => env('MAIL_LOGO_URL', null),

    'colors' => [
        'primary' => env('MAIL_BRAND_PRIMARY', '#4F46E5'),
        'primary_dark' => env('MAIL_BRAND_PRIMARY_DARK', '#4338CA'),
        'accent' => env('MAIL_BRAND_ACCENT', '#7C3AED'),
        'text' => '#1F2937',
        'text_muted' => '#6B7280',
        'background' => '#F3F4F6',
        'card' => '#FFFFFF',
        'border' => '#E5E7EB',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email verification bypass
    |--------------------------------------------------------------------------
    | Roles listed here skip verification (panel + payment). "User" must verify.
    */
    'verification_exempt_roles' => array_filter(array_map(
        'trim',
        explode(',', env('MAIL_VERIFICATION_EXEMPT_ROLES', 'Super Admin,Admin,Staff'))
    )),

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    */
    'queue' => env('MAIL_QUEUE', 'default'),
    'queue_connection' => env('MAIL_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),

];
