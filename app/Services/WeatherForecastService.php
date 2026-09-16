<?php

namespace App\Services;

use App\Models\WeatherDailyObservation;
use App\Services\Smhi\SmhiFireForecastClient;
use App\Services\Smhi\SmhiMetObsClient;
use App\Services\Smhi\SmhiSnowForecastClient;
use App\Support\FireRiskFormatter;
use App\Support\SmhiWeatherSymbol;
use App\Support\WeatherObservationFormatter;
use App\Support\WeatherSummaryFormatter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class WeatherForecastService
{
    public function __construct(
        public SmhiSnowForecastClient $forecastClient,
        public SmhiMetObsClient $metObsClient,
        public SmhiFireForecastClient $fireForecastClient,
        public WeatherWarningsService $warningsService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function presentation(): array
    {
        $cacheSeconds = (int) config('smhi_weather.forecast_cache_seconds', 1800);

        return Cache::remember(
            'smhi.weather.forecast.presentation',
            $cacheSeconds,
            function (): array {
                try {
                    return $this->buildPresentation();
                } catch (\Throwable $exception) {
                    report($exception);

                    // Cacha degraded-payload så sidomenyn inte spammar SMHI vid varje request.
                    return $this->degradedPresentation();
                }
            },
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPresentation(): array
    {
        $timezone = (string) config('smhi_weather.timezone', 'Europe/Stockholm');

        try {
            $forecastPayload = $this->forecastClient->fetchPointForecast();
        } catch (\Throwable $exception) {
            report($exception);
            $forecastPayload = [
                'reference_time' => null,
                'created_time' => null,
                'time_series' => [],
            ];
        }

        $latestObservations = $this->buildLatestObservations($timezone);

        try {
            $today = $this->buildTodayPresentation($timezone, $latestObservations);
        } catch (\Throwable $exception) {
            report($exception);
            $today = $this->emptyTodayPresentation($timezone);
        }

        $todayForecast = $this->summarizeForecastDayFromTimeSeries(
            $forecastPayload['time_series'],
            now()->timezone($timezone)->startOfDay(),
            $timezone,
        );

        if ($todayForecast !== null) {
            $today['forecast_summary'] = $todayForecast['summary'];
            $today['forecast_symbol_label'] = $todayForecast['symbol_label'];
            $today['forecast_wind_speed_max'] = $todayForecast['wind_speed_max'];
            $today['forecast_temp_max'] = $todayForecast['temp_max'];
            $today['forecast_precipitation_mm'] = $todayForecast['precipitation_mm'];
        }

        if (! isset($today['temp_now']) || ! is_numeric($today['temp_now'])) {
            $forecastTemp = $this->nearestForecastTemperature(
                $forecastPayload['time_series'],
                $timezone,
            );

            if ($forecastTemp !== null) {
                $today['temp_now'] = $forecastTemp;
                $today['temp_now_is_forecast'] = true;
            } elseif (isset($today['forecast_temp_max']) && is_numeric($today['forecast_temp_max'])) {
                $today['temp_now'] = (float) $today['forecast_temp_max'];
                $today['temp_now_is_forecast'] = true;
            }
        }

        $forecastDays = $this->buildForecastDays($forecastPayload['time_series'], $timezone);
        $forecastDays = $this->attachFireRiskToForecastDays($forecastDays, $timezone);

        return [
            'station_name' => (string) config('smhi_weather.station_name'),
            'fetched_at' => now()->timezone($timezone)->toIso8601String(),
            'reference_time' => $forecastPayload['reference_time'],
            'created_time' => $forecastPayload['created_time'],
            'today' => $today,
            'forecast_days' => $forecastDays,
            'latest_observations' => $latestObservations,
            'warnings' => $this->warningsService->presentation(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function degradedPresentation(): array
    {
        $timezone = (string) config('smhi_weather.timezone', 'Europe/Stockholm');

        return [
            'station_name' => (string) config('smhi_weather.station_name'),
            'fetched_at' => now()->timezone($timezone)->toIso8601String(),
            'reference_time' => null,
            'created_time' => null,
            'today' => $this->emptyTodayPresentation($timezone),
            'forecast_days' => [],
            'latest_observations' => [
                'available' => false,
                'rows' => [],
                'temperature' => null,
            ],
            'warnings' => $this->warningsService->presentation(),
            'degraded' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyTodayPresentation(string $timezone): array
    {
        $today = now()->timezone($timezone)->startOfDay();

        return [
            'date' => $today->toDateString(),
            'label' => $this->formatDayLabel($today),
            'summary' => null,
            'temp_now' => null,
            'temp_now_label' => null,
            'preliminary' => true,
            'available' => false,
        ];
    }

    /**
     * @param  array{available: bool, rows: list<array<string, string|null>>}  $latestObservations
     * @return array<string, mixed>
     */
    private function buildTodayPresentation(string $timezone, array $latestObservations): array
    {
        $today = now()->timezone($timezone)->startOfDay();
        $observation = $this->liveObservationForDate($today);
        $tempNow = $this->temperatureFromLatestObservations($latestObservations)
            ?? $this->latestTemperature($today);
        $tempNowLabel = $latestObservations['temperature']['observed_at_label'] ?? null;

        return [
            'date' => $today->toDateString(),
            'label' => $this->formatDayLabel($today),
            'summary' => WeatherSummaryFormatter::format($observation),
            'temp_now' => $tempNow,
            'temp_now_label' => $tempNowLabel,
            'preliminary' => true,
            'available' => $observation !== null,
        ];
    }

    /**
     * @return array{available: bool, rows: list<array<string, string|null>>}
     */
    private function buildLatestObservations(string $timezone): array
    {
        try {
            $parameters = (array) config('smhi_weather.observation_parameters', []);
            $hourlySeries = $this->metObsClient->fetchParameterSeriesBatch($parameters, 'latest-hour');
            $rows = [];

            $temperature = $this->latestSeriesEntry($hourlySeries['temperature'] ?? []);
            $temperatureMeta = null;
            if ($temperature !== null) {
                $temperatureMeta = [
                    'value' => (float) $temperature['value'],
                    'observed_at_label' => WeatherObservationFormatter::observationTimeLabel($temperature['timestamp_ms'], $timezone),
                ];
                $rows[] = [
                    'label' => 'Lufttemperatur',
                    'value' => self::formatDecimal($temperature['value']).' °C',
                    'observed_at' => $temperatureMeta['observed_at_label'],
                ];
            }

            $windSpeed = $this->latestSeriesEntry($hourlySeries['wind_speed'] ?? []);
            if ($windSpeed !== null) {
                $rows[] = [
                    'label' => 'Medelvind',
                    'value' => self::formatDecimal($windSpeed['value'], 0).' m/s',
                    'observed_at' => WeatherObservationFormatter::observationTimeLabel($windSpeed['timestamp_ms'], $timezone),
                ];
            }

            $windGust = $this->latestSeriesEntry($hourlySeries['wind_gust'] ?? []);
            if ($windGust !== null) {
                $rows[] = [
                    'label' => 'Byvind',
                    'value' => self::formatDecimal($windGust['value'], 0).' m/s',
                    'observed_at' => WeatherObservationFormatter::observationTimeLabel($windGust['timestamp_ms'], $timezone),
                ];
            }

            $windDirection = $this->latestSeriesEntry($hourlySeries['wind_direction'] ?? []);
            if ($windDirection !== null) {
                $compass = WeatherObservationFormatter::windDirection($windDirection['value']);
                $rows[] = [
                    'label' => 'Vindriktning',
                    'value' => $compass['label'],
                    'value_detail' => '('.self::formatDecimal($windDirection['value'], 0).'°) '.$compass['abbreviation'],
                    'observed_at' => WeatherObservationFormatter::observationTimeLabel($windDirection['timestamp_ms'], $timezone),
                ];
            }

            $humidity = $this->latestSeriesEntry($hourlySeries['humidity'] ?? []);
            if ($humidity !== null) {
                $rows[] = [
                    'label' => 'Luftfuktighet',
                    'value' => self::formatDecimal($humidity['value'], 0).' %',
                    'observed_at' => WeatherObservationFormatter::observationTimeLabel($humidity['timestamp_ms'], $timezone),
                ];
            }

            $precipitation = $this->latestSeriesEntry($hourlySeries['precipitation'] ?? []);
            if ($precipitation !== null) {
                $rows[] = [
                    'label' => 'Nederbörd',
                    'value' => self::formatDecimal($precipitation['value']).' mm',
                    'observed_at' => WeatherObservationFormatter::hourlyPeriodLabel($precipitation['timestamp_ms'], $timezone),
                ];
            }

            $dailyPrecipitation = $this->dailyPrecipitationSinceEight($timezone);
            if ($dailyPrecipitation !== null) {
                $rows[] = [
                    'label' => 'Nederbörd dygn',
                    'value' => self::formatDecimal($dailyPrecipitation['value']).' mm',
                    'observed_at' => WeatherObservationFormatter::dailyPrecipitationPeriodLabel($timezone),
                ];
            }

            $visibility = $this->latestSeriesEntry($hourlySeries['visibility'] ?? []);
            if ($visibility !== null) {
                $rows[] = [
                    'label' => 'Sikt',
                    'value' => WeatherObservationFormatter::formatVisibility($visibility['value']),
                    'observed_at' => WeatherObservationFormatter::observationTimeLabel($visibility['timestamp_ms'], $timezone),
                ];
            }

            return [
                'available' => $rows !== [],
                'rows' => $rows,
                'temperature' => $temperatureMeta,
            ];
        } catch (\Throwable) {
            return [
                'available' => false,
                'rows' => [],
                'temperature' => null,
            ];
        }
    }

    /**
     * @param  array{available: bool, rows: list<array<string, string|null>>}  $latestObservations
     */
    private function temperatureFromLatestObservations(array $latestObservations): ?float
    {
        foreach ($latestObservations['rows'] as $row) {
            if (($row['label'] ?? '') !== 'Lufttemperatur') {
                continue;
            }

            $value = (string) ($row['value'] ?? '');
            $numeric = str_replace([' °C', ','], ['', '.'], $value);

            return is_numeric($numeric) ? (float) $numeric : null;
        }

        return null;
    }

    /**
     * @param  list<array{0: int, 1: float, 2?: string}>  $series
     * @return array{timestamp_ms: int, value: float}|null
     */
    private function latestSeriesEntry(array $series): ?array
    {
        if ($series === []) {
            return null;
        }

        $latest = end($series);

        if (! isset($latest[0], $latest[1])) {
            return null;
        }

        return [
            'timestamp_ms' => (int) $latest[0],
            'value' => (float) $latest[1],
        ];
    }

    /**
     * @return array{value: float}|null
     */
    private function dailyPrecipitationSinceEight(string $timezone): ?array
    {
        $series = $this->metObsClient->fetchParameterSeries(
            (int) config('smhi_weather.observation_parameters.precipitation', 7),
            'latest-day',
        );

        if ($series === []) {
            return null;
        }

        $now = now()->timezone($timezone);
        $periodEnd = $now->copy()->startOfDay()->setTime(8, 0);
        if ($now->lt($periodEnd)) {
            $periodEnd->subDay();
        }

        $periodStart = $periodEnd->copy()->subDay();
        $sum = 0.0;
        $hasValues = false;

        foreach ($series as $entry) {
            if (! isset($entry[0], $entry[1])) {
                continue;
            }

            $timestamp = Carbon::createFromTimestampMs((int) $entry[0], $timezone);

            if ($timestamp->lte($periodStart) || $timestamp->gt($periodEnd)) {
                continue;
            }

            $sum += (float) $entry[1];
            $hasValues = true;
        }

        if (! $hasValues) {
            return null;
        }

        return ['value' => $sum];
    }

    private static function formatDecimal(float $value, int $decimals = 1): string
    {
        return WeatherObservationFormatter::formatDecimal($value, $decimals);
    }

    /**
     * @param  list<array{time: string, data: array<string, mixed>}>  $timeSeries
     */
    private function nearestForecastTemperature(array $timeSeries, string $timezone): ?float
    {
        $now = now()->timezone($timezone);
        $bestDiff = null;
        $bestTemp = null;

        foreach ($timeSeries as $entry) {
            if (! isset($entry['time'], $entry['data']) || ! is_array($entry['data'])) {
                continue;
            }

            if (! isset($entry['data']['air_temperature']) || ! is_numeric($entry['data']['air_temperature'])) {
                continue;
            }

            $localTime = Carbon::parse($entry['time'])->timezone($timezone);
            $diff = abs($localTime->diffInSeconds($now, false));

            if ($bestDiff === null || $diff < $bestDiff) {
                $bestDiff = $diff;
                $bestTemp = (float) $entry['data']['air_temperature'];
            }
        }

        return $bestTemp;
    }

    /**
     * @param  list<array{time: string, data: array<string, mixed>}>  $timeSeries
     * @return list<array<string, mixed>>
     */
    private function buildForecastDays(array $timeSeries, string $timezone): array
    {
        $days = (int) config('smhi_weather.forecast_days', 5);
        $tomorrow = now()->timezone($timezone)->addDay()->startOfDay();
        $endDate = $tomorrow->copy()->addDays($days - 1);

        /** @var array<string, array{temps: list<float>, winds: list<float>, gusts: list<float>, precips: list<float>, symbols: list<int>}> $grouped */
        $grouped = [];

        foreach ($timeSeries as $entry) {
            if (! isset($entry['time'], $entry['data']) || ! is_array($entry['data'])) {
                continue;
            }

            $localTime = Carbon::parse($entry['time'])->timezone($timezone);
            $dateString = $localTime->toDateString();

            if ($localTime->lt($tomorrow) || $localTime->gt($endDate->endOfDay())) {
                continue;
            }

            $data = $entry['data'];

            $grouped[$dateString]['temps'][] = (float) ($data['air_temperature'] ?? 0);
            $grouped[$dateString]['winds'][] = (float) ($data['wind_speed'] ?? 0);
            $grouped[$dateString]['gusts'][] = (float) ($data['wind_speed_of_gust'] ?? 0);
            $grouped[$dateString]['precips'][] = (float) ($data['precipitation_amount_mean'] ?? 0);

            if (isset($data['symbol_code'])) {
                $grouped[$dateString]['symbols'][] = (int) $data['symbol_code'];
            }
        }

        $rows = [];

        for ($cursor = $tomorrow->copy(); $cursor->lte($endDate); $cursor->addDay()) {
            $dateString = $cursor->toDateString();
            $bucket = $grouped[$dateString] ?? null;

            if ($bucket === null || $bucket['temps'] === []) {
                continue;
            }

            $observation = new WeatherDailyObservation([
                'temp_min' => min($bucket['temps']),
                'temp_max' => max($bucket['temps']),
                'precipitation_mm' => array_sum($bucket['precips']),
                'wind_speed_max' => max($bucket['winds']),
                'wind_gust_max' => max($bucket['gusts']),
            ]);

            $symbolCode = $this->pickSymbolCode($bucket['symbols'] ?? []);
            $summaryParts = WeatherSummaryFormatter::parts($observation);

            $rows[] = [
                'date' => $dateString,
                'label' => $this->formatDayLabel($cursor),
                'label_short' => $cursor->format('j/n'),
                'summary' => WeatherSummaryFormatter::format($observation),
                'temp_summary' => $summaryParts['temp'],
                'temp_max' => (float) $observation->temp_max,
                'wind_summary' => $summaryParts['wind'],
                'precipitation_summary' => $summaryParts['precipitation'],
                'precipitation_mm' => (float) $observation->precipitation_mm,
                'symbol_code' => $symbolCode,
                'symbol_label' => SmhiWeatherSymbol::label($symbolCode),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{time: string, data: array<string, mixed>}>  $timeSeries
     * @return array{summary: ?string, symbol_label: ?string, wind_speed_max: ?float, temp_max: ?float, precipitation_mm: ?float}|null
     */
    private function summarizeForecastDayFromTimeSeries(array $timeSeries, Carbon $day, string $timezone): ?array
    {
        $dateString = $day->toDateString();

        /** @var array{temps: list<float>, winds: list<float>, gusts: list<float>, precips: list<float>, symbols: list<int>} $bucket */
        $bucket = [
            'temps' => [],
            'winds' => [],
            'gusts' => [],
            'precips' => [],
            'symbols' => [],
        ];

        foreach ($timeSeries as $entry) {
            if (! isset($entry['time'], $entry['data']) || ! is_array($entry['data'])) {
                continue;
            }

            $localTime = Carbon::parse($entry['time'])->timezone($timezone);

            if ($localTime->toDateString() !== $dateString) {
                continue;
            }

            $data = $entry['data'];

            $bucket['temps'][] = (float) ($data['air_temperature'] ?? 0);
            $bucket['winds'][] = (float) ($data['wind_speed'] ?? 0);
            $bucket['gusts'][] = (float) ($data['wind_speed_of_gust'] ?? 0);
            $bucket['precips'][] = (float) ($data['precipitation_amount_mean'] ?? 0);

            if (isset($data['symbol_code'])) {
                $bucket['symbols'][] = (int) $data['symbol_code'];
            }
        }

        if ($bucket['temps'] === []) {
            return null;
        }

        $tempMax = max($bucket['temps']);
        $precipitationMm = array_sum($bucket['precips']);
        $windSpeedMax = max($bucket['winds']);

        $observation = new WeatherDailyObservation([
            'temp_min' => min($bucket['temps']),
            'temp_max' => $tempMax,
            'precipitation_mm' => $precipitationMm,
            'wind_speed_max' => $windSpeedMax,
            'wind_gust_max' => max($bucket['gusts']),
        ]);

        $symbolCode = $this->pickSymbolCode($bucket['symbols']);

        return [
            'summary' => WeatherSummaryFormatter::format($observation),
            'symbol_label' => SmhiWeatherSymbol::label($symbolCode),
            'wind_speed_max' => $windSpeedMax,
            'temp_max' => $tempMax,
            'precipitation_mm' => $precipitationMm,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function attachFireRiskToForecastDays(array $rows, string $timezone): array
    {
        try {
            $firePayload = $this->fireForecastClient->fetchDailyPointForecast();
            $riskByDate = $this->fireRiskByDate($firePayload['time_series'], $timezone);
        } catch (\Throwable) {
            return $rows;
        }

        foreach ($rows as $index => $row) {
            $date = (string) ($row['date'] ?? '');
            $risk = $riskByDate[$date] ?? null;

            $rows[$index]['fire_risk_index'] = $risk['index'] ?? null;
            $rows[$index]['fire_risk_label'] = $risk['label'] ?? null;
            $rows[$index]['fire_risk_summary'] = FireRiskFormatter::presentation(
                $risk['index'] ?? null,
                $risk['label'] ?? null,
            );
        }

        return $rows;
    }

    /**
     * @param  list<array{valid_time: string, parameters: list<array<string, mixed>>}>  $timeSeries
     * @return array<string, array{index: int|null, label: string|null}>
     */
    private function fireRiskByDate(array $timeSeries, string $timezone): array
    {
        $riskByDate = [];

        foreach ($timeSeries as $entry) {
            $localDate = Carbon::parse($entry['valid_time'])->timezone($timezone)->toDateString();
            $fwiIndex = $this->parameterValue($entry['parameters'], 'fwiindex');

            if ($fwiIndex === null) {
                continue;
            }

            $risk = FireRiskFormatter::fromFwiIndex($fwiIndex);

            if ($risk['index'] === null) {
                continue;
            }

            $riskByDate[$localDate] = $risk;
        }

        return $riskByDate;
    }

    /**
     * @param  list<array<string, mixed>>  $parameters
     */
    private function parameterValue(array $parameters, string $name): ?float
    {
        foreach ($parameters as $parameter) {
            if (! is_array($parameter) || ($parameter['name'] ?? null) !== $name) {
                continue;
            }

            $values = $parameter['values'] ?? [];

            if (! is_array($values) || $values === []) {
                return null;
            }

            return (float) $values[0];
        }

        return null;
    }

    private function liveObservationForDate(Carbon $date): ?WeatherDailyObservation
    {
        $parameters = (array) config('smhi_weather.parameters', []);
        $dateString = $date->toDateString();

        /** @var array<string, list<float>> $seriesByParameter */
        $seriesByParameter = [];

        $latestDaySeries = $this->metObsClient->fetchParameterSeriesBatch($parameters, 'latest-day');

        foreach ($parameters as $key => $parameterId) {
            $seriesByParameter[$key] = $this->metObsClient->groupHourlyByLocalDate(
                $latestDaySeries[$key] ?? [],
            );
        }

        $temperatures = $seriesByParameter['temperature'][$dateString] ?? [];
        $precipitation = $seriesByParameter['precipitation'][$dateString] ?? [];
        $windSpeed = $seriesByParameter['wind_speed'][$dateString] ?? [];
        $windGust = $seriesByParameter['wind_gust'][$dateString] ?? [];

        if ($temperatures === [] && $precipitation === [] && $windSpeed === [] && $windGust === []) {
            return null;
        }

        return new WeatherDailyObservation([
            'observation_date' => $dateString,
            'station_name' => (string) config('smhi_weather.station_name'),
            'station_id' => (int) config('smhi_weather.station_id'),
            'temp_min' => $temperatures !== [] ? min($temperatures) : null,
            'temp_max' => $temperatures !== [] ? max($temperatures) : null,
            'precipitation_mm' => $precipitation !== [] ? array_sum($precipitation) : null,
            'wind_speed_max' => $windSpeed !== [] ? max($windSpeed) : null,
            'wind_gust_max' => $windGust !== [] ? max($windGust) : null,
        ]);
    }

    private function latestTemperature(Carbon $date): ?float
    {
        $values = $this->metObsClient->fetchParameterSeries(
            (int) config('smhi_weather.parameters.temperature'),
            'latest-hour',
        );

        if ($values === []) {
            return null;
        }

        $latest = end($values);

        return isset($latest[1]) ? (float) $latest[1] : null;
    }

    /**
     * @param  list<int>  $symbols
     */
    private function pickSymbolCode(array $symbols): ?int
    {
        if ($symbols === []) {
            return null;
        }

        $counts = array_count_values($symbols);
        arsort($counts);

        return (int) array_key_first($counts);
    }

    /**
     * @return array<string, mixed>
     */
    public function sidebarSnippet(): array
    {
        try {
            $presentation = $this->presentation();
            $today = $presentation['today'] ?? [];
            $tomorrow = $presentation['forecast_days'][0] ?? null;

            if (! ($today['available'] ?? false) && $tomorrow === null) {
                return $this->fallbackSidebarSnippet();
            }

            return [
                'station_label' => 'Lungö',
                'temp_now' => $today['temp_now'] ?? null,
                'temp_now_label' => $today['temp_now_label'] ?? null,
                'today_forecast_text' => WeatherSummaryFormatter::dayGlance(
                    isset($today['forecast_symbol_label']) ? (string) $today['forecast_symbol_label'] : null,
                    isset($today['forecast_temp_max']) && is_numeric($today['forecast_temp_max'])
                        ? (float) $today['forecast_temp_max']
                        : null,
                    isset($today['forecast_precipitation_mm']) && is_numeric($today['forecast_precipitation_mm'])
                        ? (float) $today['forecast_precipitation_mm']
                        : null,
                ),
                'tomorrow_text' => is_array($tomorrow)
                    ? WeatherSummaryFormatter::dayGlance(
                        isset($tomorrow['symbol_label']) ? (string) $tomorrow['symbol_label'] : null,
                        isset($tomorrow['temp_max']) && is_numeric($tomorrow['temp_max'])
                            ? (float) $tomorrow['temp_max']
                            : null,
                        isset($tomorrow['precipitation_mm']) && is_numeric($tomorrow['precipitation_mm'])
                            ? (float) $tomorrow['precipitation_mm']
                            : null,
                    )
                    : null,
                'warning_alert' => $this->warningsService->sidebarAlert(),
            ];
        } catch (\Throwable) {
            return $this->fallbackSidebarSnippet();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackSidebarSnippet(): array
    {
        return [
            'station_label' => 'Lungö',
            'unavailable' => true,
        ];
    }

    private function formatDayLabel(Carbon $date): string
    {
        $label = $date->locale('sv')->isoFormat('dddd D MMMM');

        return mb_strtoupper(mb_substr($label, 0, 1)).mb_substr($label, 1);
    }
}
