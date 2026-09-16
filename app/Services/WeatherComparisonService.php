<?php

namespace App\Services;

use App\Models\WeatherDailyObservation;
use App\Support\WeatherSummaryFormatter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WeatherComparisonService
{
    public function summaryForDate(Carbon $date): ?string
    {
        return WeatherSummaryFormatter::format($this->observationForDate($date));
    }

    public function summaryForYear(Carbon $date, int $calendarYear): ?string
    {
        return WeatherSummaryFormatter::format($this->observationForYear($date, $calendarYear));
    }

    public function observationForDate(Carbon $date): ?WeatherDailyObservation
    {
        return WeatherDailyObservation::query()
            ->whereDate('observation_date', $date->toDateString())
            ->first();
    }

    /**
     * @param  list<string>  $dates
     * @return Collection<string, WeatherDailyObservation>
     */
    public function observationsForDates(array $dates): Collection
    {
        if ($dates === []) {
            return collect();
        }

        return WeatherDailyObservation::query()
            ->where(function ($query) use ($dates) {
                foreach ($dates as $date) {
                    $query->orWhereDate('observation_date', $date);
                }
            })
            ->get()
            ->keyBy(fn (WeatherDailyObservation $observation) => $observation->observation_date->toDateString());
    }

    public function observationForYear(Carbon $date, int $calendarYear): ?WeatherDailyObservation
    {
        return WeatherDailyObservation::query()
            ->where('iso_week', (int) $date->isoWeek)
            ->where('iso_weekday', (int) $date->dayOfWeekIso)
            ->whereBetween('observation_date', [
                Carbon::create($calendarYear, 1, 1)->toDateString(),
                Carbon::create($calendarYear, 12, 31)->toDateString(),
            ])
            ->first();
    }

    /**
     * @param  list<int>  $baselineYears
     * @return array{by_year: array<int, string|null>, current: string|null}
     */
    public function rowWeather(
        Carbon $date,
        array $baselineYears,
        int $comparisonYear,
        bool $includeComparisonYearWeather,
    ): array {
        $byYear = [];

        foreach ($baselineYears as $year) {
            $byYear[$year] = $this->summaryForYear($date, $year);
        }

        $comparisonWeather = null;

        if ($includeComparisonYearWeather) {
            $comparisonWeather = $this->summaryForYear($date, $comparisonYear);
            $byYear[$comparisonYear] = $comparisonWeather;
        }

        return [
            'by_year' => $byYear,
            'current' => $comparisonWeather,
        ];
    }
}
