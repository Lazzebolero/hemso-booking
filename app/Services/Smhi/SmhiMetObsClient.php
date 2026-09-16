<?php

namespace App\Services\Smhi;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class SmhiMetObsClient
{
    /**
     * @return list<array{0: int, 1: float, 2?: string}>
     */
    public function fetchParameterSeries(int $parameterId, string $period = 'corrected-archive'): array
    {
        if ($period === 'corrected-archive') {
            return $this->parseCsvSeries($this->fetchCsvBody($parameterId, $period));
        }

        $response = $this->requestData($parameterId, $period, 'json');

        if ($response->notFound()) {
            try {
                return $this->parseCsvSeries($this->fetchCsvBody($parameterId, $period));
            } catch (\Throwable) {
                return [];
            }
        }

        if (! $response->successful()) {
            return [];
        }

        /** @var list<array{0: int, 1: float|null, 2?: string}> $values */
        $values = $response->json('value', []);

        return $this->normalizeValues($values);
    }

    /**
     * @param  list<array{0: int, 1: float|null, 2?: string}>  $values
     * @return array<string, list<float>>
     */
    public function groupHourlyByLocalDate(array $values): array
    {
        $timezone = (string) config('smhi_weather.timezone', 'Europe/Stockholm');
        $grouped = [];

        foreach ($values as $entry) {
            if (! isset($entry[0], $entry[1])) {
                continue;
            }

            $date = Carbon::createFromTimestampMs((int) $entry[0], $timezone)->toDateString();
            $grouped[$date][] = (float) $entry[1];
        }

        return $grouped;
    }

    public function probeConnection(): bool
    {
        try {
            $this->fetchParameterSeries(
                (int) config('smhi_weather.parameters.temperature'),
                'latest-months',
            );

            return true;
        } catch (RequestException) {
            return false;
        }
    }

    private function fetchCsvBody(int $parameterId, string $period): string
    {
        $response = $this->requestData($parameterId, $period, 'csv');

        if (! $response->successful()) {
            throw new RequestException($response);
        }

        return $response->body();
    }

    private function requestData(int $parameterId, string $period, string $format): Response
    {
        $url = sprintf(
            '%s/parameter/%d/station/%d/period/%s/data.%s',
            rtrim((string) config('smhi_weather.api_base_url'), '/'),
            $parameterId,
            (int) config('smhi_weather.station_id'),
            $period,
            $format,
        );

        $timeout = in_array($period, ['latest-day', 'latest-hour', 'latest-months'], true) ? 30 : 180;

        return Http::timeout($timeout)
            ->retry(2, 500)
            ->get($url);
    }

    /**
     * @param  array<string, int>  $parameters
     * @return array<string, list<array{0: int, 1: float, 2?: string}>>
     */
    public function fetchParameterSeriesBatch(array $parameters, string $period): array
    {
        /** @var array<string, Response> $responses */
        $responses = Http::pool(function (Pool $pool) use ($parameters, $period): void {
            foreach ($parameters as $key => $parameterId) {
                $url = sprintf(
                    '%s/parameter/%d/station/%d/period/%s/data.json',
                    rtrim((string) config('smhi_weather.api_base_url'), '/'),
                    (int) $parameterId,
                    (int) config('smhi_weather.station_id'),
                    $period,
                );

                $pool->as((string) $key)
                    ->timeout(30)
                    ->retry(2, 500)
                    ->get($url);
            }
        });

        $series = [];

        foreach ($parameters as $key => $parameterId) {
            $response = $responses[(string) $key] ?? null;

            // Http::pool() kan returnera RequestException/ConnectionException vid fel — inte Response.
            if (! $response instanceof Response || ! $response->successful()) {
                $series[(string) $key] = [];

                continue;
            }

            /** @var list<array{0: int, 1: float|null, 2?: string}> $values */
            $values = $response->json('value', []);
            $series[(string) $key] = $this->normalizeValues($values);
        }

        return $series;
    }

    /**
     * @param  list<array<string, mixed>|array{0: int, 1: float|null, 2?: string}>  $values
     * @return list<array{0: int, 1: float, 2?: string}>
     */
    private function normalizeValues(array $values): array
    {
        $normalized = [];

        foreach ($values as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            if (array_key_exists('date', $entry) && array_key_exists('value', $entry)) {
                if ($entry['value'] === '' || $entry['value'] === null) {
                    continue;
                }

                $normalized[] = [
                    (int) $entry['date'],
                    (float) $entry['value'],
                    isset($entry['quality']) ? (string) $entry['quality'] : null,
                ];

                continue;
            }

            if (! isset($entry[0], $entry[1]) || $entry[1] === null) {
                continue;
            }

            $normalized[] = [(int) $entry[0], (float) $entry[1], $entry[2] ?? null];
        }

        return $normalized;
    }

    /**
     * @return list<array{0: int, 1: float, 2?: string}>
     */
    private function parseCsvSeries(string $csv): array
    {
        $values = [];
        $inData = false;

        foreach (preg_split('/\r\n|\n|\r/', $csv) ?: [] as $line) {
            if (! $inData) {
                if (str_starts_with($line, 'Datum;')) {
                    $inData = true;
                }

                continue;
            }

            $parts = str_getcsv($line, ';', '"', '\\');

            if (count($parts) < 3) {
                continue;
            }

            [$date, $time, $value] = $parts;

            if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                continue;
            }

            if ($value === '' || ! is_numeric($value)) {
                continue;
            }

            $timestamp = Carbon::parse("{$date} {$time}", 'UTC')->getTimestampMs();
            $values[] = [$timestamp, (float) $value, $parts[3] ?? null];
        }

        return $values;
    }
}
