<?php

namespace App\Services\Smhi;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class SmhiFireForecastClient
{
    /**
     * @return array{
     *     approved_time: string|null,
     *     reference_time: string|null,
     *     time_series: list<array{valid_time: string, parameters: list<array<string, mixed>}>}
     * }
     */
    public function fetchDailyPointForecast(): array
    {
        $longitude = number_format((float) config('smhi_weather.forecast_longitude'), 6, '.', '');
        $latitude = number_format((float) config('smhi_weather.forecast_latitude'), 6, '.', '');
        $baseUrl = rtrim((string) config('smhi_weather.fire_forecast_api_url'), '/');

        $url = "{$baseUrl}/lon/{$longitude}/lat/{$latitude}/data.json";

        $response = Http::timeout(10)
            ->retry(1, 500)
            ->get($url);

        $response->throw();

        /** @var list<array{validTime: string, parameters: list<array<string, mixed>>}> $timeSeries */
        $timeSeries = $response->json('timeSeries', []);

        $normalized = [];

        foreach ($timeSeries as $entry) {
            if (! isset($entry['validTime'], $entry['parameters']) || ! is_array($entry['parameters'])) {
                continue;
            }

            $normalized[] = [
                'valid_time' => (string) $entry['validTime'],
                'parameters' => $entry['parameters'],
            ];
        }

        return [
            'approved_time' => $response->json('approvedTime'),
            'reference_time' => $response->json('referenceTime'),
            'time_series' => $normalized,
        ];
    }

    public function probeConnection(): bool
    {
        try {
            $this->fetchDailyPointForecast();

            return true;
        } catch (RequestException) {
            return false;
        }
    }
}
