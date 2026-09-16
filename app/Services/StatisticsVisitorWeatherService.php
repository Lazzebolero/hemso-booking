<?php

namespace App\Services;

use App\Models\WeatherDailyObservation;
use App\Support\WeatherSummaryFormatter;
use Illuminate\Support\Collection;

class StatisticsVisitorWeatherService
{
    public function __construct(private WeatherComparisonService $weatherComparison) {}

    /**
     * @param  array<int, array{date: string, date_label: string, weekday: string, booked: int, tours: int, guides?: int}>  $days
     * @return array<int, array{date: string, date_label: string, weekday: string, booked: int, tours: int, guides?: int, weather_summary: ?string}>
     */
    public function attachWeatherToDays(array $days): array
    {
        if ($days === []) {
            return [];
        }

        $observations = $this->weatherComparison->observationsForDates(
            array_column($days, 'date'),
        );

        return array_map(function (array $day) use ($observations): array {
            $observation = $observations->get($day['date']);

            return [
                ...$day,
                'weather_summary' => WeatherSummaryFormatter::format($observation),
            ];
        }, $days);
    }

    /**
     * @param  array<int, array{date: string, date_label: string, weekday: string, booked: int, tours: int, guides?: int}>  $dailyCounts
     * @return array{hottest: ?array{date: string, date_label: string, weekday: string, booked: int, tours: int, guides?: int, temp_max: float, temp_min: float|null, weather_summary: ?string}, coldest: ?array{date: string, date_label: string, weekday: string, booked: int, tours: int, guides?: int, temp_max: float, temp_min: float|null, weather_summary: ?string}, wettest: ?array{date: string, date_label: string, weekday: string, booked: int, tours: int, guides?: int, precipitation_mm: float, weather_summary: ?string}, windiest: ?array{date: string, date_label: string, weekday: string, booked: int, tours: int, guides?: int, wind_gust_max: float, weather_summary: ?string}}
     */
    public function extremesFromDailyCounts(array $dailyCounts): array
    {
        $visitorDays = collect($dailyCounts)
            ->filter(fn (array $day) => $day['booked'] > 0)
            ->values();

        if ($visitorDays->isEmpty()) {
            return [
                'hottest' => null,
                'coldest' => null,
                'wettest' => null,
                'windiest' => null,
            ];
        }

        $observations = $this->weatherComparison->observationsForDates(
            $visitorDays->pluck('date')->all(),
        );

        return [
            'hottest' => $this->resolveHottestDay($visitorDays, $observations),
            'coldest' => $this->resolveColdestDay($visitorDays, $observations),
            'wettest' => $this->resolveWettestDay($visitorDays, $observations),
            'windiest' => $this->resolveWindiestDay($visitorDays, $observations),
        ];
    }

    /**
     * @param  Collection<int, array{date: string, date_label: string, weekday: string, booked: int, tours: int}>  $visitorDays
     * @param  Collection<string, WeatherDailyObservation>  $observations
     * @return array{date: string, date_label: string, weekday: string, booked: int, tours: int, temp_max: float, temp_min: float|null, weather_summary: ?string}|null
     */
    private function resolveHottestDay(Collection $visitorDays, Collection $observations): ?array
    {
        $candidates = $visitorDays
            ->map(function (array $day) use ($observations): ?array {
                $observation = $observations->get($day['date']);

                if ($observation?->temp_max === null) {
                    return null;
                }

                return [
                    ...$day,
                    'temp_max' => (float) $observation->temp_max,
                    'temp_min' => $observation->temp_min !== null ? (float) $observation->temp_min : null,
                    'weather_summary' => WeatherSummaryFormatter::format($observation),
                ];
            })
            ->filter()
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates
            ->sort(function (array $left, array $right): int {
                $temperatureComparison = $right['temp_max'] <=> $left['temp_max'];

                if ($temperatureComparison !== 0) {
                    return $temperatureComparison;
                }

                $bookedComparison = $right['booked'] <=> $left['booked'];

                if ($bookedComparison !== 0) {
                    return $bookedComparison;
                }

                return strcmp($left['date'], $right['date']);
            })
            ->first();
    }

    /**
     * @param  Collection<int, array{date: string, date_label: string, weekday: string, booked: int, tours: int}>  $visitorDays
     * @param  Collection<string, WeatherDailyObservation>  $observations
     * @return array{date: string, date_label: string, weekday: string, booked: int, tours: int, temp_max: float, temp_min: float|null, weather_summary: ?string}|null
     */
    private function resolveColdestDay(Collection $visitorDays, Collection $observations): ?array
    {
        $candidates = $visitorDays
            ->map(function (array $day) use ($observations): ?array {
                $observation = $observations->get($day['date']);

                if ($observation?->temp_max === null) {
                    return null;
                }

                return [
                    ...$day,
                    'temp_max' => (float) $observation->temp_max,
                    'temp_min' => $observation->temp_min !== null ? (float) $observation->temp_min : null,
                    'weather_summary' => WeatherSummaryFormatter::format($observation),
                ];
            })
            ->filter()
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates
            ->sort(function (array $left, array $right): int {
                $temperatureComparison = $left['temp_max'] <=> $right['temp_max'];

                if ($temperatureComparison !== 0) {
                    return $temperatureComparison;
                }

                $bookedComparison = $right['booked'] <=> $left['booked'];

                if ($bookedComparison !== 0) {
                    return $bookedComparison;
                }

                return strcmp($left['date'], $right['date']);
            })
            ->first();
    }

    /**
     * @param  Collection<int, array{date: string, date_label: string, weekday: string, booked: int, tours: int}>  $visitorDays
     * @param  Collection<string, WeatherDailyObservation>  $observations
     * @return array{date: string, date_label: string, weekday: string, booked: int, tours: int, precipitation_mm: float, weather_summary: ?string}|null
     */
    private function resolveWettestDay(Collection $visitorDays, Collection $observations): ?array
    {
        $candidates = $visitorDays
            ->map(function (array $day) use ($observations): ?array {
                $observation = $observations->get($day['date']);

                if ($observation?->precipitation_mm === null) {
                    return null;
                }

                $precipitationMm = (float) $observation->precipitation_mm;

                if ($precipitationMm <= 0) {
                    return null;
                }

                return [
                    ...$day,
                    'precipitation_mm' => $precipitationMm,
                    'weather_summary' => WeatherSummaryFormatter::format($observation),
                ];
            })
            ->filter()
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates
            ->sort(function (array $left, array $right): int {
                $precipitationComparison = $right['precipitation_mm'] <=> $left['precipitation_mm'];

                if ($precipitationComparison !== 0) {
                    return $precipitationComparison;
                }

                $bookedComparison = $right['booked'] <=> $left['booked'];

                if ($bookedComparison !== 0) {
                    return $bookedComparison;
                }

                return strcmp($left['date'], $right['date']);
            })
            ->first();
    }

    /**
     * @param  Collection<int, array{date: string, date_label: string, weekday: string, booked: int, tours: int}>  $visitorDays
     * @param  Collection<string, WeatherDailyObservation>  $observations
     * @return array{date: string, date_label: string, weekday: string, booked: int, tours: int, wind_gust_max: float, weather_summary: ?string}|null
     */
    private function resolveWindiestDay(Collection $visitorDays, Collection $observations): ?array
    {
        $candidates = $visitorDays
            ->map(function (array $day) use ($observations): ?array {
                $observation = $observations->get($day['date']);
                $windGustMax = $this->resolveWindGustMax($observation);

                if ($windGustMax === null || $windGustMax <= 0) {
                    return null;
                }

                return [
                    ...$day,
                    'wind_gust_max' => $windGustMax,
                    'weather_summary' => WeatherSummaryFormatter::format($observation),
                ];
            })
            ->filter()
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates
            ->sort(function (array $left, array $right): int {
                $windComparison = $right['wind_gust_max'] <=> $left['wind_gust_max'];

                if ($windComparison !== 0) {
                    return $windComparison;
                }

                $bookedComparison = $right['booked'] <=> $left['booked'];

                if ($bookedComparison !== 0) {
                    return $bookedComparison;
                }

                return strcmp($left['date'], $right['date']);
            })
            ->first();
    }

    private function resolveWindGustMax(?WeatherDailyObservation $observation): ?float
    {
        if ($observation === null) {
            return null;
        }

        if ($observation->wind_gust_max !== null) {
            return (float) $observation->wind_gust_max;
        }

        if ($observation->wind_speed_max !== null) {
            return (float) $observation->wind_speed_max;
        }

        return null;
    }
}
