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

    'restaurant_statistics' => [

        'password' => env('RESTAURANT_STATISTICS_PASSWORD'),
    ],
    'security_alerts' => [
        'email' => env('SECURITY_ALERT_EMAIL', env('MAIL_FROM_ADDRESS')),
        'failed_threshold' => env('SECURITY_ALERT_FAILED_THRESHOLD', 8),
        'minutes' => env('SECURITY_ALERT_MINUTES', 15),
        'cooldown_minutes' => env('SECURITY_ALERT_COOLDOWN_MINUTES', 60),
    ],

    'system_health' => [
        'monitor_token' => env('SYSTEM_HEALTH_MONITOR_TOKEN'),
        'report_email' => env('SYSTEM_HEALTH_REPORT_EMAIL', env('SECURITY_ALERT_EMAIL', env('MAIL_FROM_ADDRESS'))),
        'daily_report_time' => env('SYSTEM_HEALTH_DAILY_REPORT_TIME', '07:00'),
    ],

];
