<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'facebook' => [
        'client_id' => env('FACEBOOK_APP_ID', env('FACEBOOK_CLIENT_ID')),
        'client_secret' => env('FACEBOOK_APP_SECRET', env('FACEBOOK_CLIENT_SECRET')),
        'config_id' => env('FACEBOOK_CONFIG_ID'),
        'redirect' => env('FACEBOOK_REDIRECT_URL'),
    ],

    'instagram' => [
        'app_id' => env('INSTAGRAM_APP_ID') ?: env('FACEBOOK_APP_ID', env('FACEBOOK_CLIENT_ID')),
        'app_secret' => env('INSTAGRAM_APP_SECRET') ?: env('FACEBOOK_APP_SECRET', env('FACEBOOK_CLIENT_SECRET')),
        'image_public_base_url' => env('INSTAGRAM_IMAGE_PUBLIC_BASE_URL'),
        'login_redirect' => env('INSTAGRAM_LOGIN_REDIRECT_URI'),
        'oauth_authorize_url' => env('INSTAGRAM_OAUTH_AUTHORIZE_URL', 'https://www.instagram.com/oauth/authorize'),
        /** Match Instagram authorize URL: force_reauth=true (see Meta OAuth authorize). */
        'oauth_force_reauth' => filter_var(env('INSTAGRAM_OAUTH_FORCE_REAUTH', true), FILTER_VALIDATE_BOOLEAN),
        'oauth_enable_fb_login' => env('INSTAGRAM_OAUTH_ENABLE_FB_LOGIN', '0'),
        'graph_version' => ltrim((string) env('FACEBOOK_GRAPH_VERSION', 'v21.0'), '/'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

];
