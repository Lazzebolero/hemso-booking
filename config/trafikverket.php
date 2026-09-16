<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trafikverket trafikinfo API (Hemsöleden)
    |--------------------------------------------------------------------------
    |
    | Registrera nyckel: https://www.trafikverket.se/e-tjanster/trafikverkets-oppna-api-for-trafikinformation/
    |
    */

    'api_key' => env('TRAFIKVERKET_API_KEY'),

    'endpoint' => env('TRAFIKVERKET_API_ENDPOINT', 'https://api.trafikinfo.trafikverket.se/v2/data.json'),

    'route_name' => env('TRAFIKVERKET_FERRY_ROUTE', 'Hemsöleden'),

    'harbors' => [
        'strinningen' => env('TRAFIKVERKET_HARBOR_STRINNINGEN', 'Strinningen'),
        'hemso' => env('TRAFIKVERKET_HARBOR_HEMSO', 'Hemsö'),
    ],

    'cache_ttl_seconds' => (int) env('TRAFIKVERKET_CACHE_TTL', 180),

    'sync_days_ahead' => 1,

    /*
    | Uppskattad körtid färjeläget → anläggningen (för gästvarning vid extratur).
    */
    'guest_travel_minutes_min' => (int) env('FERRY_GUEST_TRAVEL_MINUTES_MIN', 15),

    'guest_travel_minutes_max' => (int) env('FERRY_GUEST_TRAVEL_MINUTES_MAX', 20),

    'extra_departure_alert_minutes' => (int) env('FERRY_EXTRA_DEPARTURE_ALERT_MINUTES', 25),

];
