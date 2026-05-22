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
    | Failed post emails
    |--------------------------------------------------------------------------
    | Max failed-post notification emails per user per calendar day (UTC).
    */
    'failed_post_email_daily_limit' => (int) env('MAIL_FAILED_POST_DAILY_LIMIT', 10),

    /*
    | Set true (or use APP_ENV=local) to enable GET panel/test/failed-post-email.
    */
    'failed_post_email_test_route' => filter_var(env('MAIL_FAILED_POST_TEST_ROUTE', false), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | Weekly connected accounts report
    |--------------------------------------------------------------------------
    | Sent to all verified panel users (role User) via reports:weekly-accounts.
    | Schedule: Sunday 15:00 in app timezone (config/app.php).
    */
    'weekly_accounts_report_enabled' => filter_var(env('MAIL_WEEKLY_ACCOUNTS_REPORT_ENABLED', true), FILTER_VALIDATE_BOOL),

    'weekly_accounts_report_test_route' => filter_var(env('MAIL_WEEKLY_ACCOUNTS_REPORT_TEST_ROUTE', false), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    */
    'queue' => env('MAIL_QUEUE', 'default'),
    'queue_connection' => env('MAIL_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),

];
