<?php

namespace App\Models;

use Database\Factories\WeatherDailyObservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class WeatherDailyObservation extends Model
{
    /** @use HasFactory<WeatherDailyObservationFactory> */
    use HasFactory;

    protected $fillable = [
        'observation_date',
        'station_name',
        'station_id',
        'temp_min',
        'temp_max',
        'precipitation_mm',
        'wind_speed_max',
        'wind_gust_max',
        'iso_week',
        'iso_week_year',
        'iso_weekday',
        'synced_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'observation_date' => 'date',
            'temp_min' => 'decimal:1',
            'temp_max' => 'decimal:1',
            'precipitation_mm' => 'decimal:1',
            'wind_speed_max' => 'decimal:1',
            'wind_gust_max' => 'decimal:1',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * @return array{iso_week: int, iso_week_year: int, iso_weekday: int}
     */
    public static function isoAttributesFromDate(Carbon $date): array
    {
        return [
            'iso_week' => (int) $date->isoWeek,
            'iso_week_year' => (int) $date->isoWeekYear,
            'iso_weekday' => (int) $date->dayOfWeekIso,
        ];
    }
}
