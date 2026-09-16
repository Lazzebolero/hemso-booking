<?php

namespace App\Services\Smhi;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class SmhiSnowForecastClient
{
    /**
     * @return array{
     *     reference_time: string|null,
     *     created_time: string|null,
     *     time_series: list<array{time: string, data: array<string, mixed>}>
     * }
     */
    public function fetchPointForecast(): array
    {
        $longitude = number_format((float) config('smhi_weather.forecast_longitude'), 6, '.', '');
        $latitude = number_format((float) config('smhi_weather.forecast_latitude'), 6, '.', '');
        $baseUrl = rtrim((string) config('smhi_weather.forecast_api_url'), '/');

        $url = "{$baseUrl}/lon/{$longitude}/lat/{$latitude}/data.json";

        $response = Http::timeout(15)
            ->retry(1, 500)
            ->get($url);

        $response->throw();

        /** @var list<array{time: string, data: array<string, mixed>}> $timeSeries */
        $timeSeries = $response->json('timeSeries', []);

        return [
            'reference_time' => $response->json('referenceTime'),
            'created_time' => $response->json('createdTime'),
            'time_series' => $timeSeries,
        ];
    }

    public function probeConnection(): bool
    {
        try {
            $this->fetchPointForecast();

            return true;
        } catch (RequestException) {
            return false;
        }
    }
}
