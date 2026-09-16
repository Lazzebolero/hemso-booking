<?php

namespace App\Services\Smhi;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class SmhiWeatherWarningsClient
{
    /**
     * @return list<array<string, mixed>>
     */
    public function fetchActiveWarnings(): array
    {
        $url = (string) config('smhi_weather.warnings_api_url');

        $response = Http::timeout(10)
            ->retry(1, 500)
            ->get($url);

        $response->throw();

        /** @var list<array<string, mixed>> $warnings */
        $warnings = $response->json() ?? [];

        return $warnings;
    }

    public function probeConnection(): bool
    {
        try {
            $this->fetchActiveWarnings();

            return true;
        } catch (RequestException) {
            return false;
        }
    }
}
