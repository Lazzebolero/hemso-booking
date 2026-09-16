<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMHI MetObs – Lungö A (kuststation nära Hemsö)
    |--------------------------------------------------------------------------
    */

    'station_id' => 128390,
    'station_name' => 'Lungö A',
    'timezone' => 'Europe/Stockholm',
    'api_base_url' => 'https://opendata-download-metobs.smhi.se/api/version/latest',

    'parameters' => [
        'temperature' => 1,
        'wind_speed' => 4,
        'precipitation' => 7,
        'wind_gust' => 21,
    ],

    // Senaste timobservationer (visas på prognossidan, samma parametrar som SMHI Lungö).
    'observation_parameters' => [
        'temperature' => 1,
        'wind_speed' => 4,
        'wind_gust' => 21,
        'wind_direction' => 3,
        'humidity' => 6,
        'precipitation' => 7,
        'visibility' => 12,
    ],

    'default_backfill_from' => '2024-01-01',

    /*
    |--------------------------------------------------------------------------
    | SMHI SNOW1g – punktprognos (Lungö / Hemsö)
    |--------------------------------------------------------------------------
    */

    'forecast_latitude' => 62.6417,
    'forecast_longitude' => 18.0899,
    'forecast_days' => 5,
    'forecast_cache_seconds' => 300,
    'public_status_cache_seconds' => (int) env('SMHI_PUBLIC_STATUS_CACHE_SECONDS', 90),
    'forecast_api_url' => 'https://opendata-download-metfcst.smhi.se/api/category/snow1g/version/1/geotype/point',

    'fire_forecast_api_url' => 'https://opendata-download-metfcst.smhi.se/api/category/fwif1g/version/1/daily/geotype/point',

    /*
    |--------------------------------------------------------------------------
    | SMHI IBWW – konsekvensbaserade vädervarningar
    |--------------------------------------------------------------------------
    */

    'warnings_api_url' => 'https://opendata-download-warnings.smhi.se/ibww/api/version/1/warning.json',
    'warnings_cache_seconds' => 300,

    // Västernorrlands län + närliggande havsområden (färjetrafik).
    'warning_area_ids' => [22, 44, 46],

    // Meteorologiska händelser som är relevanta för drift på Hemsö.
    'warning_event_codes' => [
        'THUNDER',
        'WIND',
        'WIND_SNOW',
        'SNOW',
        'RAIN',
        'WIND_SEA',
        'STRONG_COOLING',
        'BLACK_ICE',
        'HIGH_FLOW',
        'FLOODING',
        'FIRE',
        'HIGH_TEMPERATURES',
    ],

];
