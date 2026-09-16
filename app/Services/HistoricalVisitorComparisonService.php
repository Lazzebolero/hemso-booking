<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\HistoricalDailyVisitor;
use App\Models\StatisticsDayNote;
use App\Models\Tour;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class HistoricalVisitorComparisonService
{
    /**
     * @param  list<int>  $baselineYears
     * @return list<array<string, mixed>>
     */
    public function dailySeries(Carbon $from, Carbon $to, array $baselineYears): array
    {
        $forecastService = app(HistoricalVisitorForecastService::class);
        $weatherService = app(WeatherComparisonService::class);
        $today = now()->startOfDay();
        $tourMetricsByDate = $this->tourAndGuideMetricsByDate($from, $to);
        $dayNotes = Schema::hasTable('statistics_day_notes')
            ? StatisticsDayNote::mapForRange($from, $to)
            : [];
        $days = [];

        for ($cursor = $from->copy()->startOfDay(); $cursor->lte($to); $cursor->addDay()) {
            $current = $this->currentParticipantsForDate($cursor);
            $isPast = $cursor->lte($today);
            $dateKey = $cursor->toDateString();
            $dayMetrics = $tourMetricsByDate[$dateKey] ?? [
                'tours_count' => 0,
                'guides_count' => 0,
            ];

            $historicalByYear = [];
            foreach ($baselineYears as $year) {
                $historicalByYear[$year] = $this->historicalParticipantsForYear($cursor, $year);
            }

            $forecast = $forecastService->forecastForDate($cursor, $baselineYears);
            $comparisonYear = (int) $cursor->year;
            $weather = $weatherService->rowWeather(
                $cursor,
                $baselineYears,
                $comparisonYear,
                $cursor->lt($today),
            );

            $row = [
                'date' => $dateKey,
                'calendar_year' => (int) $cursor->year,
                'label' => $this->formatComparisonLabel($cursor),
                'label_short' => $cursor->format('j/n'),
                'iso_week' => (int) $cursor->isoWeek,
                'current' => $current,
                'tours_count' => $dayMetrics['tours_count'],
                'guides_count' => $dayMetrics['guides_count'],
                'day_note' => $dayNotes[$dateKey] ?? null,
                'historical_by_year' => $historicalByYear,
                'weather_by_year' => $weather['by_year'],
                'weather_current' => $weather['current'],
                'forecast' => $forecast !== null ? (int) round($forecast) : null,
                'is_past' => $isPast,
            ];

            $days[] = $row;
        }

        return $days;
    }

    /**
     * @param  list<array<string, mixed>>  $series
     * @param  list<int>  $baselineYears
     * @return array<string, mixed>
     */
    public function summarizeComparison(array $series, array $baselineYears): array
    {
        $pastRows = collect($series)->where('is_past', true);

        $summary = [
            'current' => (int) $pastRows->sum('current'),
            'days' => $pastRows->count(),
            'tours' => (int) $pastRows->sum('tours_count'),
            'guide_days' => (int) $pastRows->sum('guides_count'),
        ];

        foreach ($baselineYears as $year) {
            $summary["historical_{$year}"] = (int) $pastRows->sum(
                fn (array $row) => $row['historical_by_year'][$year] ?? 0
            );
        }

        return $summary;
    }

    public function formatComparisonLabel(Carbon $date): string
    {
        $label = $date->locale('sv')->isoFormat('dddd D MMMM');

        return mb_strtoupper(mb_substr($label, 0, 1)).mb_substr($label, 1);
    }

    /**
     * @param  list<array<string, mixed>>  $series
     * @return array{forecast: int, days: int}
     */
    public function summarizeForecast(array $series): array
    {
        $rows = collect($series);

        return [
            'forecast' => (int) $rows->sum(fn (array $row) => $row['forecast'] ?? 0),
            'days' => $rows->count(),
        ];
    }

    public function currentParticipantsForDate(Carbon $date): int
    {
        return (int) Booking::query()
            ->where('is_waitlist', false)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('tour', fn ($query) => $query->whereDate('tour_date', $date->toDateString()))
            ->sum('total_count');
    }

    /**
     * Antal ej avbokade turer och unika guider (huvudguide + medguider) per dag.
     *
     * @return array<string, array{tours_count: int, guides_count: int}>
     */
    public function tourAndGuideMetricsByDate(Carbon $from, Carbon $to): array
    {
        $query = Tour::query()
            ->whereDate('tour_date', '>=', $from->toDateString())
            ->whereDate('tour_date', '<=', $to->toDateString())
            ->where('status', '!=', 'cancelled');

        if (Schema::hasTable('tour_guide')) {
            $query->with('coGuides:id');
        }

        $metrics = [];

        foreach ($query->get(['id', 'tour_date', 'guide_id']) as $tour) {
            $dateKey = $tour->tour_date?->toDateString();

            if ($dateKey === null) {
                continue;
            }

            if (! isset($metrics[$dateKey])) {
                $metrics[$dateKey] = [
                    'tours_count' => 0,
                    'guide_ids' => [],
                ];
            }

            $metrics[$dateKey]['tours_count']++;

            if ($tour->guide_id) {
                $metrics[$dateKey]['guide_ids'][(int) $tour->guide_id] = true;
            }

            if ($tour->relationLoaded('coGuides')) {
                foreach ($tour->coGuides as $coGuide) {
                    $metrics[$dateKey]['guide_ids'][(int) $coGuide->id] = true;
                }
            }
        }

        $result = [];

        foreach ($metrics as $dateKey => $day) {
            $result[$dateKey] = [
                'tours_count' => (int) $day['tours_count'],
                'guides_count' => count($day['guide_ids']),
            ];
        }

        return $result;
    }

    public function historicalParticipantsForYear(Carbon $date, int $calendarYear): ?int
    {
        $count = HistoricalDailyVisitor::query()
            ->where('iso_week', (int) $date->isoWeek)
            ->where('iso_weekday', (int) $date->dayOfWeekIso)
            ->whereBetween('stat_date', [
                Carbon::create($calendarYear, 1, 1)->toDateString(),
                Carbon::create($calendarYear, 12, 31)->toDateString(),
            ])
            ->value('participant_count');

        return $count !== null ? (int) $count : null;
    }

    /**
     * @param  list<array<string, mixed>>  $series
     * @param  list<int>  $baselineYears
     * @return array{labels: list<string>, tooltips: list<string>, datasets: list<array<string, mixed>>}
     */
    public function chartPayload(array $series, array $baselineYears, int $comparisonYear): array
    {
        if ($series === []) {
            return [
                'labels' => [],
                'tooltips' => [],
                'datasets' => [],
            ];
        }

        $yearPalette = [
            ['border' => '#059669', 'background' => 'rgba(5, 150, 105, 0.1)'],
            ['border' => '#d97706', 'background' => 'rgba(217, 119, 6, 0.1)'],
        ];

        $datasets = [];

        foreach ($baselineYears as $index => $year) {
            $color = $yearPalette[$index % count($yearPalette)];

            $datasets[] = [
                'label' => (string) $year,
                'data' => array_map(
                    fn (array $row) => isset($row['historical_by_year'][$year])
                        ? (int) $row['historical_by_year'][$year]
                        : null,
                    $series
                ),
                'borderColor' => $color['border'],
                'backgroundColor' => $color['background'],
                'fill' => false,
                'tension' => 0.25,
                'borderWidth' => 2,
                'pointRadius' => 2,
                'pointHoverRadius' => 4,
            ];
        }

        $datasets[] = [
            'label' => 'Prognos',
            'data' => array_map(
                fn (array $row) => $row['forecast'] !== null ? (int) $row['forecast'] : null,
                $series
            ),
            'borderColor' => '#7c3aed',
            'backgroundColor' => 'rgba(124, 58, 237, 0.08)',
            'borderDash' => [6, 4],
            'fill' => false,
            'tension' => 0.25,
            'borderWidth' => 2,
            'pointRadius' => 2,
            'pointHoverRadius' => 4,
        ];

        $datasets[] = [
            'label' => "Bokade {$comparisonYear}",
            'data' => array_map(fn (array $row) => (int) $row['current'], $series),
            'borderColor' => '#2563eb',
            'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
            'fill' => true,
            'tension' => 0.3,
            'borderWidth' => 3,
            'pointRadius' => 3,
            'pointHoverRadius' => 5,
        ];

        return [
            'labels' => array_map(fn (array $row) => (string) $row['label_short'], $series),
            'tooltips' => array_map(fn (array $row) => (string) $row['label'], $series),
            'datasets' => $datasets,
        ];
    }

    /**
     * @return list<int>
     */
    public function resolveBaselineYears(): array
    {
        $years = HistoricalDailyVisitor::query()
            ->orderBy('stat_date')
            ->pluck('stat_date')
            ->map(fn ($statDate) => Carbon::parse($statDate)->year)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($years === []) {
            return [2024, 2025];
        }

        if (count($years) >= 2) {
            return array_slice($years, -2);
        }

        return $years;
    }
}
