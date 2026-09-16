<?php

namespace App\Services;

use App\Support\FerryDirections;
use Illuminate\Support\Facades\Cache;

class PublicSiteStatusService
{
    public function __construct(
        private WeatherForecastService $weatherForecastService,
        private FerryScheduleService $ferryScheduleService,
    ) {}

    /**
     * @return array{
     *     generated_at: string,
     *     weather: array<string, mixed>,
     *     ferry: array<string, mixed>
     * }
     */
    public function status(): array
    {
        $cacheSeconds = max(30, (int) config('smhi_weather.public_status_cache_seconds', 90));

        return Cache::remember(
            'public.site-status.v2',
            $cacheSeconds,
            fn (): array => [
                'generated_at' => now()->toIso8601String(),
                'weather' => $this->buildWeather(),
                'ferry' => $this->buildFerry(),
            ],
        );
    }

    /**
     * @return array{
     *     available: bool,
     *     temperature_c: ?float,
     *     temperature_label: ?string,
     *     summary: ?string,
     *     symbol_label: ?string,
     *     wind_label: ?string,
     *     station_name: ?string,
     *     text: string
     * }
     */
    private function buildWeather(): array
    {
        try {
            $presentation = $this->weatherForecastService->presentation();
            $today = is_array($presentation['today'] ?? null) ? $presentation['today'] : [];
            $todayDate = (string) ($today['date'] ?? now()->toDateString());

            $forecastDay = collect($presentation['forecast_days'] ?? [])
                ->first(fn ($day): bool => is_array($day) && ($day['date'] ?? null) === $todayDate);

            $temperature = isset($today['temp_now']) && is_numeric($today['temp_now'])
                ? (float) $today['temp_now']
                : null;

            if ($temperature === null && isset($today['forecast_temp_max']) && is_numeric($today['forecast_temp_max'])) {
                $temperature = (float) $today['forecast_temp_max'];
            }

            if ($temperature === null && is_array($forecastDay) && isset($forecastDay['temp_max']) && is_numeric($forecastDay['temp_max'])) {
                $temperature = (float) $forecastDay['temp_max'];
            }

            $symbolLabel = $today['forecast_symbol_label']
                ?? (is_array($forecastDay) ? ($forecastDay['symbol_label'] ?? null) : null);
            $symbolLabel = is_string($symbolLabel) && $symbolLabel !== '' ? $symbolLabel : null;

            $windMs = $this->resolveWindSpeedMs($presentation, $today, is_array($forecastDay) ? $forecastDay : null);
            $windLabel = $this->windPhrase($windMs);

            $parts = array_values(array_filter([
                $temperature !== null ? $this->formatTemperature($temperature) : null,
                $symbolLabel,
                $windLabel,
            ]));

            $available = $parts !== [];

            return [
                'available' => $available,
                'temperature_c' => $temperature !== null ? round($temperature, 1) : null,
                'temperature_label' => $temperature !== null ? $this->formatTemperature($temperature) : null,
                'summary' => $symbolLabel,
                'symbol_label' => $symbolLabel,
                'wind_label' => $windLabel,
                'station_name' => isset($presentation['station_name']) ? (string) $presentation['station_name'] : null,
                'text' => $available ? implode(' · ', $parts) : 'Väderdata saknas just nu',
            ];
        } catch (\Throwable $exception) {
            report($exception);

            return [
                'available' => false,
                'temperature_c' => null,
                'temperature_label' => null,
                'summary' => null,
                'symbol_label' => null,
                'wind_label' => null,
                'station_name' => null,
                'text' => 'Väderdata saknas just nu',
            ];
        }
    }

    /**
     * @return array{
     *     available: bool,
     *     direction: string,
     *     direction_label: string,
     *     from_label: string,
     *     time: ?string,
     *     minutes_until: ?int,
     *     status_label: ?string,
     *     delay_minutes: ?int,
     *     date: ?string,
     *     is_today: bool,
     *     text: string
     * }
     */
    private function buildFerry(): array
    {
        $direction = FerryDirections::TO_ISLAND;
        $directionLabel = 'Till Hemsön';
        $fromLabel = FerryDirections::harborLabels()[$direction] ?? 'Strinningen';

        try {
            $now = now();
            $snapshot = $this->ferryScheduleService->daySnapshot($now, $direction, $now);
            $next = is_array($snapshot['next'] ?? null) ? $snapshot['next'] : null;
            $date = (string) ($snapshot['date'] ?? $now->toDateString());
            $isToday = true;

            if ($next === null) {
                $tomorrow = $now->copy()->addDay()->startOfDay();
                $snapshot = $this->ferryScheduleService->daySnapshot($tomorrow, $direction, $now);
                $next = is_array($snapshot['next'] ?? null) ? $snapshot['next'] : null;
                $date = (string) ($snapshot['date'] ?? $tomorrow->toDateString());
                $isToday = false;
            }

            if ($next === null || ! empty($next['is_cancelled'])) {
                return [
                    'available' => false,
                    'direction' => $direction,
                    'direction_label' => $directionLabel,
                    'from_label' => $fromLabel,
                    'time' => null,
                    'minutes_until' => null,
                    'status_label' => null,
                    'delay_minutes' => null,
                    'date' => null,
                    'is_today' => true,
                    'text' => 'Färjedata saknas just nu',
                ];
            }

            $time = (string) ($next['time'] ?? '');
            $minutesUntil = isset($next['minutes_until']) ? (int) $next['minutes_until'] : null;
            $delayMinutes = isset($next['delay_minutes']) ? (int) $next['delay_minutes'] : null;
            $statusLabel = isset($next['status_label']) ? (string) $next['status_label'] : null;

            $parts = array_values(array_filter([
                $time !== '' ? $time : null,
                $directionLabel,
                ! $isToday ? 'i morgon' : null,
                $minutesUntil !== null ? 'om '.$minutesUntil.' min' : null,
                $delayMinutes !== null && $delayMinutes > 0 ? 'försenad '.$delayMinutes.' min' : null,
            ]));

            return [
                'available' => $time !== '',
                'direction' => $direction,
                'direction_label' => $directionLabel,
                'from_label' => $fromLabel,
                'time' => $time !== '' ? $time : null,
                'minutes_until' => $minutesUntil,
                'status_label' => $statusLabel,
                'delay_minutes' => $delayMinutes,
                'date' => $date,
                'is_today' => $isToday,
                'text' => $parts !== [] ? implode(' · ', $parts) : 'Färjedata saknas just nu',
            ];
        } catch (\Throwable $exception) {
            report($exception);

            return [
                'available' => false,
                'direction' => $direction,
                'direction_label' => $directionLabel,
                'from_label' => $fromLabel,
                'time' => null,
                'minutes_until' => null,
                'status_label' => null,
                'delay_minutes' => null,
                'date' => null,
                'is_today' => true,
                'text' => 'Färjedata saknas just nu',
            ];
        }
    }

    private function formatTemperature(float $temperature): string
    {
        return (string) (int) round($temperature).'°';
    }

    /**
     * @param  array<string, mixed>  $presentation
     * @param  array<string, mixed>  $today
     * @param  array<string, mixed>|null  $forecastDay
     */
    private function resolveWindSpeedMs(array $presentation, array $today, ?array $forecastDay): ?float
    {
        $observationRows = $presentation['latest_observations']['rows'] ?? null;

        if (is_array($observationRows)) {
            foreach ($observationRows as $row) {
                if (! is_array($row) || ($row['label'] ?? null) !== 'Medelvind') {
                    continue;
                }

                if (preg_match('/([\d]+(?:[.,]\d+)?)/', (string) ($row['value'] ?? ''), $matches) === 1) {
                    return (float) str_replace(',', '.', $matches[1]);
                }
            }
        }

        if (isset($today['forecast_wind_speed_max']) && is_numeric($today['forecast_wind_speed_max'])) {
            return (float) $today['forecast_wind_speed_max'];
        }

        if (isset($forecastDay['wind_speed_max']) && is_numeric($forecastDay['wind_speed_max'])) {
            return (float) $forecastDay['wind_speed_max'];
        }

        return null;
    }

    private function windPhrase(?float $metersPerSecond): ?string
    {
        if ($metersPerSecond === null) {
            return null;
        }

        return match (true) {
            $metersPerSecond < 1.5 => 'vindstilla',
            $metersPerSecond < 5.0 => 'svag vind',
            $metersPerSecond < 10.0 => 'måttlig vind',
            $metersPerSecond < 15.0 => 'frisk vind',
            default => 'hård vind',
        };
    }
}
