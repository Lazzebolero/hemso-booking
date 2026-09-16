<?php

use App\Support\Roles;

return [

    /*
    |--------------------------------------------------------------------------
    | Anläggningens referenspunkt (valfritt)
    |--------------------------------------------------------------------------
    | Används i admin för att visa ungefärligt avstånd vid granskning.
    */
    'facility_latitude' => env('TIME_CLOCK_FACILITY_LAT'),
    'facility_longitude' => env('TIME_CLOCK_FACILITY_LNG'),

    /*
    |--------------------------------------------------------------------------
    | Roller som får stämpla via QR
    |--------------------------------------------------------------------------
    */
    'clock_roles' => [
        Roles::GUIDE,
        Roles::HOST,
        Roles::RESTAURANT,
        Roles::ADMIN,
    ],

    /*
    |--------------------------------------------------------------------------
    | QR-stämpelstationer
    |--------------------------------------------------------------------------
    | Alla clock_roles kan stämpla vid båda stationerna. In/ut sparas som
    | clock_in_station respektive clock_out_station på time_entries.
    | Sätt unika tokens i .env. Skriv ut QR som pekar på route('time.scan', ...).
    */
    'stations' => [
        'entrance' => [
            'label' => 'Entré',
            'description' => 'QR vid entrén',
            'token' => env('TIME_CLOCK_TOKEN_ENTRANCE'),
        ],
        'restaurant' => [
            'label' => 'Restaurang',
            'description' => 'QR vid restaurangen',
            'token' => env('TIME_CLOCK_TOKEN_RESTAURANT'),
        ],
    ],
];
