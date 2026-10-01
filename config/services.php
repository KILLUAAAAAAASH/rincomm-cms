<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | Credentials and configuration for external services belong here so
    | application code does not access environment variables directly.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Semaphore SMS
    |--------------------------------------------------------------------------
    |
    | Semaphore is used for Philippine SMS verification-code delivery.
    | Secrets remain in the local environment and are never committed.
    |
    */

    'semaphore' => [
        'base_url' => env(
            'SEMAPHORE_BASE_URL',
            'https://api.semaphore.co'
        ),

        'api_key' => env('SEMAPHORE_API_KEY'),

        'sender_name' => env('SEMAPHORE_SENDER_NAME'),

        'timeout' => (int) env(
            'SEMAPHORE_TIMEOUT',
            10
        ),
    ],

];
