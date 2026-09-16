<?php

namespace App\Services;

use App\Models\WeatherDailyObservation;
use App\Services\Smhi\SmhiMetObsClient;
use Illuminate\Support\Carbon;

class WeatherObservationSyncService
{
    public function __construct(public SmhiMetObsClient $client) {}

    public function syncDateRange(Carbon $from, Carbon $to, bool $useArchive = true): int
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        if ($from->gt($to)) {
            return 0;
        }

        $period = $useArchive ? 'corrected-archive' : 'latest-months';
        $parameters = (array) config('smhi_weather.parameters', []);

        /** @var array<string, array<string, list<float>>> $seriesByParameter */
        $seriesByParameter = $this->loadSeriesByParameter($parameters, $period);

        if ($useArchive) {
            $recentSeries = $this->loadSeriesByParameter($parameters, 'latest-months');
            $seriesByParameter = $this->mergeSeries($seriesByParameter, $recentSeries);
        }

        $dates = [];

        foreach ($seriesByParameter as $grouped) {
            $dates = array_merge($dates, array_keys($grouped));
        }

        $dates = array_values(array_unique($dates));
        sort($dates);

        $saved = 0;

        foreach ($dates as $dateString) {
            $date = Carbon::parse($dateString)->startOfDay();

            if ($date->lt($from) || $date->gt($to)) {
                continue;
            }

            $temperatures = $seriesByParameter['temperature'][$dateString] ?? [];
            $precipitation = $seriesByParameter['precipitation'][$dateString] ?? [];
            $windSpeed = $seriesByParameter['wind_speed'][$dateString] ?? [];
            $windGust = $seriesByParameter['wind_gust'][$dateString] ?? [];

            if ($temperatures === [] && $precipitation === [] && $windSpeed === [] && $windGust === []) {
                continue;
            }

            WeatherDailyObservation::query()->updateOrCreate(
                ['observation_date' => $dateString],
                [
                    'station_name' => (string) config('smhi_weather.station_name'),
                    'station_id' => (int) config('smhi_weather.station_id'),
                    ...WeatherDailyObservation::isoAttributesFromDate($date),
                    'temp_min' => $temperatures !== [] ? min($temperatures) : null,
                    'temp_max' => $temperatures !== [] ? max($temperatures) : null,
                    'precipitation_mm' => $precipitation !== [] ? array_sum($precipitation) : null,
                    'wind_speed_max' => $windSpeed !== [] ? max($windSpeed) : null,
                    'wind_gust_max' => $windGust !== [] ? max($windGust) : null,
                    'synced_at' => now(),
                ],
            );

            $saved++;
        }

        return $saved;
    }

    /**
     * @param  array<string, int>  $parameters
     * @return array<string, array<string, list<float>>>
     */
    private function loadSeriesByParameter(array $parameters, string $period): array
    {
        $seriesByParameter = [];

        foreach ($parameters as $key => $parameterId) {
            $seriesByParameter[$key] = $this->client->groupHourlyByLocalDate(
                $this->client->fetchParameterSeries((int) $parameterId, $period),
            );
        }

        return $seriesByParameter;
    }

    /**
     * @param  array<string, array<string, list<float>>>  $base
     * @param  array<string, array<string, list<float>>>  $overlay
     * @return array<string, array<string, list<float>>>
     */
    private function mergeSeries(array $base, array $overlay): array
    {
        foreach ($overlay as $parameter => $dates) {
            foreach ($dates as $date => $values) {
                $base[$parameter][$date] = $values;
            }
        }

        return $base;
    }
}
