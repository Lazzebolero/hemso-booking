<?php

namespace Database\Factories;

use App\Models\WeatherDailyObservation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<WeatherDailyObservation>
 */
class WeatherDailyObservationFactory extends Factory
{
    protected $model = WeatherDailyObservation::class;

    public function definition(): array
    {
        $date = Carbon::parse(fake()->dateTimeBetween('-2 years', '-1 day'));
        $tempMin = fake()->randomFloat(1, -5, 12);
        $tempMax = $tempMin + fake()->randomFloat(1, 2, 14);

        return [
            'observation_date' => $date->toDateString(),
            'station_name' => 'Lungö A',
            'station_id' => 128390,
            'temp_min' => $tempMin,
            'temp_max' => $tempMax,
            'precipitation_mm' => fake()->randomFloat(1, 0, 12),
            'wind_speed_max' => fake()->randomFloat(1, 1, 12),
            'wind_gust_max' => fake()->randomFloat(1, 3, 18),
            ...WeatherDailyObservation::isoAttributesFromDate($date),
            'synced_at' => now(),
        ];
    }

    public function onDate(string $date, array $overrides = []): static
    {
        $carbon = Carbon::parse($date);

        return $this->state(fn () => array_merge([
            'observation_date' => $carbon->toDateString(),
            ...WeatherDailyObservation::isoAttributesFromDate($carbon),
        ], $overrides));
    }
}
