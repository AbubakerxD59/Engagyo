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

    /*
    | Base URL Meta can use to fetch post media for Content Publishing (HTTPS, public). Defaults to APP_URL.
    | Used for image_url (photos). Videos typically use S3 public URLs from fetchFromS3.
    | Set INSTAGRAM_IMAGE_PUBLIC_BASE_URL when APP_URL is internal but assets are on a public host/CDN.
    */
    'instagram' => [
        'image_public_base_url' => env('INSTAGRAM_IMAGE_PUBLIC_BASE_URL'),
        /** When true, registers GET /panel/schedule/dev/instagram-carousel-test (auth required). */
        'carousel_test_enabled' => true,
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

];
