<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Raspberry Pi database access
    |--------------------------------------------------------------------------
    |
    | Pi clients poll the loudspeakers/sounds tables directly via MySQL.
    | Use a dedicated read-only MySQL user and allow remote access from the
    | Pi network. Username and password come only from AUDIO_FLEET_PI_DB_*
    | — they never fall back to the app's DB_USERNAME / DB_PASSWORD.
    |
    */

    'pi_database' => [
        'host' => env('AUDIO_FLEET_PI_DB_HOST', env('DB_HOST', '127.0.0.1')),
        'database' => env('AUDIO_FLEET_PI_DB_DATABASE', env('DB_DATABASE', 'laravel')),
        'username' => env('AUDIO_FLEET_PI_DB_USERNAME'),
        'password' => env('AUDIO_FLEET_PI_DB_PASSWORD'),
    ],

    'poll_interval_seconds' => (int) env('AUDIO_FLEET_POLL_INTERVAL', 2),

];
