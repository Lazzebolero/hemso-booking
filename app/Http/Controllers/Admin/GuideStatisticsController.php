<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuideStatisticsController extends Controller
{
    public function index(Request $request)
    {
        [$period, $date, $from, $to, $previousFrom, $previousTo] = $this->resolveRequestPeriod($request);

        $currentTours = $this->loadTours($from, $to);
        $previousTours = $this->loadTours($previousFrom, $previousTo);

        $currentStats = $this->buildGuideStats($currentTours);
        $previousStats = $this->buildGuideStats($previousTours);

        $guideRows = $this->mergeGuideComparisons($currentStats, $previousStats)
            ->sortByDesc('booked_people')
            ->values();

        $summary = $this->buildSummary($guideRows);
        $previousSummary = $this->buildSummary($previousStats);

        $chartData = [
            'labels' => $guideRows->pluck('guide_name')->values(),
            'tours' => $guideRows->pluck('tours_count')->values(),
            'booked' => $guideRows->pluck('booked_people')->values(),
            'avgPerTour' => $guideRows->pluck('avg_per_tour')->values(),
            'avgDuration' => $guideRows->pluck('avg_duration_minutes')->values(),
            'nonSwedish' => $guideRows->pluck('non_swedish_tours')->values(),
        ];

        $bestGuide = $guideRows->sortByDesc('booked_people')->first();
        $rankingByTours = $guideRows->sortByDesc('tours_count')->values()->take(5);
        $rankingByBooked = $guideRows->sortByDesc('booked_people')->values()->take(5);
        $rankingByAverage = $guideRows->sortByDesc('avg_per_tour')->values()->take(5);

        $languageShare = $this->buildLanguageShare($currentTours);

        return view('admin.statistics.guides.index', [
            'period' => $period,
            'date' => $date,
            'from' => $from,
            'to' => $to,
            'previousFrom' => $previousFrom,
            'previousTo' => $previousTo,
            'guideRows' => $guideRows,
            'summary' => $summary,
            'previousSummary' => $previousSummary,
            'chartData' => $chartData,
            'bestGuide' => $bestGuide,
            'rankingByTours' => $rankingByTours,
            'rankingByBooked' => $rankingByBooked,
            'rankingByAverage' => $rankingByAverage,
            'languageShare' => $languageShare,
        ]);
    }

    public function show(Request $request, User $user)
    {
        [$period, $date, $from, $to, $previousFrom, $previousTo] = $this->resolveRequestPeriod($request);

        $currentTours = $this->loadTours($from, $to, $user->id);
        $previousTours = $this->loadTours($previousFrom, $previousTo, $user->id);

        $currentStats = $this->buildGuideStats($currentTours)->first();
        $previousStats = $this->buildGuideStats($previousTours)->first();

        $stats = $currentStats ?: $this->emptyGuideStats($user);
        $previousStats = $previousStats ?: $this->emptyGuideStats($user);

        $timeline = $this->buildGuideTimeline($currentTours, $period, $from, $to);
        $tourTypeChart = $this->buildGuideTourTypeChart($currentTours);
        $languageChart = $this->buildGuideLanguageChart($currentTours);
        $languageShare = $this->buildLanguageShare($currentTours);

        $recentTours = $currentTours
            ->sortByDesc(function ($tour) {
                return ($tour->tour_date ?? '') . ' ' . ($tour->start_time ?? '');
            })
            ->values()
            ->take(20)
            ->map(function ($tour) {
                $tour->booked_people_count = $this->bookedPeopleForTour($tour);
                $tour->resolved_duration_minutes = $this->resolveTourDurationMinutes($tour);
                $tour->language_codes = $this->languageCodesForTour($tour);
                return $tour;
            });

        return view('admin.statistics.guides.show', [
            'guide' => $user,
            'period' => $period,
            'date' => $date,
            'from' => $from,
            'to' => $to,
            'previousFrom' => $previousFrom,
            'previousTo' => $previousTo,
            'stats' => $stats,
            'previousStats' => $previousStats,
            'timeline' => $timeline,
            'tourTypeChart' => $tourTypeChart,
            'languageChart' => $languageChart,
            'languageShare' => $languageShare,
            'recentTours' => $recentTours,
        ]);
    }

    public function tourType(Request $request, User $user, TourType $tourType)
    {
        [$period, $date, $from, $to, $previousFrom, $previousTo] = $this->resolveRequestPeriod($request);

        $tours = $this->loadTours($from, $to, $user->id)
            ->filter(fn ($tour) => (int) $tour->tour_type_id === (int) $tourType->id)
            ->values();

        $previousTours = $this->loadTours($previousFrom, $previousTo, $user->id)
            ->filter(fn ($tour) => (int) $tour->tour_type_id === (int) $tourType->id)
            ->values();

        $stats = $this->buildSingleSetStats($tours);
        $previousStats = $this->buildSingleSetStats($previousTours);

        $timeline = $this->buildGuideTimeline($tours, $period, $from, $to);
        $languageShare = $this->buildLanguageShare($tours);

        $recentTours = $tours
            ->sortByDesc(function ($tour) {
                return ($tour->tour_date ?? '') . ' ' . ($tour->start_time ?? '');
            })
            ->values()
            ->take(20)
            ->map(function ($tour) {
                $tour->booked_people_count = $this->bookedPeopleForTour($tour);
                $tour->resolved_duration_minutes = $this->resolveTourDurationMinutes($tour);
                $tour->language_codes = $this->languageCodesForTour($tour);
                return $tour;
            });

        return view('admin.statistics.guides.tour-type', [
            'guide' => $user,
            'tourType' => $tourType,
            'period' => $period,
            'date' => $date,
            'from' => $from,
            'to' => $to,
            'stats' => $stats,
            'previousStats' => $previousStats,
            'timeline' => $timeline,
            'languageShare' => $languageShare,
            'recentTours' => $recentTours,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$period, $date, $from, $to, $previousFrom, $previousTo] = $this->resolveRequestPeriod($request);

        $currentTours = $this->loadTours($from, $to);
        $previousTours = $this->loadTours($previousFrom, $previousTo);

        $currentStats = $this->buildGuideStats($currentTours);
        $previousStats = $this->buildGuideStats($previousTours);

        $guideRows = $this->mergeGuideComparisons($currentStats, $previousStats)
            ->sortByDesc('booked_people')
            ->values();

        $filename = 'guidestatistik_' . $period . '_' . $date->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($guideRows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Guide',
                'Antal turer',
                'Bokade personer',
                'Snitt per tur',
                'Snittid (min)',
                'Ej svenska',
                'Turer förändring %',
                'Bokade förändring %',
                'Turtyper',
            ], ';');

            foreach ($guideRows as $row) {
                fputcsv($handle, [
                    $row['guide_name'],
                    $row['tours_count'],
                    $row['booked_people'],
                    $row['avg_per_tour'],
                    $row['avg_duration_minutes'],
                    $row['non_swedish_tours'],
                    $row['tours_change_percent'],
                    $row['booked_change_percent'],
                    $row['tour_type_counts']->map(fn ($count, $type) => $type . ': ' . $count)->implode(', '),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function resolveRequestPeriod(Request $request): array
    {
        $period = $request->get('period', 'month');
        $date = $request->filled('date')
            ? Carbon::parse($request->get('date'))
            : now();

        [$from, $to] = $this->resolvePeriod($period, $date);
        [$previousFrom, $previousTo] = $this->resolvePreviousPeriod($from, $to);

        return [$period, $date, $from, $to, $previousFrom, $previousTo];
    }

    private function resolvePeriod(string $period, Carbon $date): array
    {
        return match ($period) {
            'day' => [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
            ],
            'week' => [
                $date->copy()->startOfWeek(Carbon::MONDAY),
                $date->copy()->endOfWeek(Carbon::SUNDAY),
            ],
            'year' => [
                $date->copy()->startOfYear(),
                $date->copy()->endOfYear(),
            ],
            default => [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
            ],
        };
    }

    private function resolvePreviousPeriod(Carbon $from, Carbon $to): array
    {
        $days = $from->copy()->startOfDay()->diffInDays($to->copy()->endOfDay()) + 1;

        $previousTo = $from->copy()->subDay()->endOfDay();
        $previousFrom = $previousTo->copy()->subDays($days - 1)->startOfDay();

        return [$previousFrom, $previousTo];
    }

    private function loadTours(Carbon $from, Carbon $to, ?int $guideId = null): Collection
    {
        $query = Tour::with([
                'guide',
                'tourType',
                'bookings.languages',
            ])
            ->whereBetween('tour_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('guide_id');

        if ($guideId) {
            $query->where('guide_id', $guideId);
        }

        return $query->get();
    }

    private function buildGuideStats(Collection $tours): Collection
    {
        return $tours
            ->groupBy('guide_id')
            ->map(function (Collection $guideTours, $guideId) {
                $guide = optional($guideTours->first())->guide;
                return $this->buildSingleSetStats($guideTours, $guideId, $guide?->name);
            })
            ->values();
    }

    private function buildSingleSetStats(Collection $tours, ?int $guideId = null, ?string $guideName = null): array
    {
        $tourTypeCounts = $tours
            ->groupBy(fn ($tour) => optional($tour->tourType)->name ?? 'Okänd')
            ->map(fn ($group) => $group->count())
            ->sortDesc();

        $bookedPeople = $tours->sum(fn ($tour) => $this->bookedPeopleForTour($tour));

        $durationMinutesTotal = 0;
        $durationToursCount = 0;

        foreach ($tours as $tour) {
            $duration = $this->resolveTourDurationMinutes($tour);

            if ($duration > 0) {
                $durationMinutesTotal += $duration;
                $durationToursCount++;
            }
        }

        $nonSwedishTours = $tours->filter(function ($tour) {
            $codes = $this->languageCodesForTour($tour)
                ->map(fn ($code) => mb_strtolower($code))
                ->values();

            return $codes->contains(fn ($code) => $code !== 'sv');
        })->count();

        return [
            'guide_id' => $guideId,
            'guide_name' => $guideName ?? 'Okänd guide',
            'tours_count' => $tours->count(),
            'booked_people' => $bookedPeople,
            'avg_per_tour' => $tours->count() > 0 ? round($bookedPeople / $tours->count(), 1) : 0,
            'duration_minutes_total' => $durationMinutesTotal,
            'duration_tours_count' => $durationToursCount,
            'avg_duration_minutes' => $durationToursCount > 0 ? round($durationMinutesTotal / $durationToursCount) : 0,
            'non_swedish_tours' => $nonSwedishTours,
            'tour_type_counts' => $tourTypeCounts,
        ];
    }

    private function mergeGuideComparisons(Collection $currentStats, Collection $previousStats): Collection
    {
        return $currentStats->map(function ($row) use ($previousStats) {
            $previous = $previousStats->firstWhere('guide_id', $row['guide_id']);

            $row['previous_tours_count'] = $previous['tours_count'] ?? 0;
            $row['previous_booked_people'] = $previous['booked_people'] ?? 0;
            $row['previous_avg_per_tour'] = $previous['avg_per_tour'] ?? 0;
            $row['previous_avg_duration_minutes'] = $previous['avg_duration_minutes'] ?? 0;
            $row['previous_non_swedish_tours'] = $previous['non_swedish_tours'] ?? 0;

            $row['tours_change_percent'] = $this->changePercent($row['tours_count'], $row['previous_tours_count']);
            $row['booked_change_percent'] = $this->changePercent($row['booked_people'], $row['previous_booked_people']);
            $row['duration_change_percent'] = $this->changePercent($row['avg_duration_minutes'], $row['previous_avg_duration_minutes']);

            return $row;
        });
    }

    private function buildSummary(Collection $guideRows): array
    {
        return [
            'guides_count' => $guideRows->count(),
            'tours_count' => $guideRows->sum('tours_count'),
            'booked_people' => $guideRows->sum('booked_people'),
            'non_swedish_tours' => $guideRows->sum('non_swedish_tours'),
            'avg_per_tour' => $guideRows->sum('tours_count') > 0
                ? round($guideRows->sum('booked_people') / $guideRows->sum('tours_count'), 1)
                : 0,
            'avg_duration_minutes' => $guideRows->sum('duration_tours_count') > 0
                ? round($guideRows->sum('duration_minutes_total') / $guideRows->sum('duration_tours_count'))
                : 0,
        ];
    }

    private function buildGuideTimeline(Collection $tours, string $period, Carbon $from, Carbon $to): array
    {
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        $labels = [];
        $booked = [];
        $toursCount = [];

        while ($cursor->lte($end)) {
            $label = match ($period) {
                'day' => $cursor->format('H:00'),
                'week' => $cursor->locale('sv')->isoFormat('ddd D/M'),
                'year' => $cursor->locale('sv')->isoFormat('MMM'),
                default => $cursor->format('d/m'),
            };

            if ($period === 'day') {
                $groupTours = $tours->filter(function ($tour) use ($cursor) {
                    if (empty($tour->start_time) || empty($tour->tour_date)) {
                        return false;
                    }

                    $tourAt = Carbon::parse($tour->tour_date)
                        ->setTimeFromTimeString($this->normalizeTime($tour->start_time));

                    return $tourAt->format('Y-m-d H') === $cursor->format('Y-m-d H');
                });

                $cursor->addHour();
            } elseif ($period === 'year') {
                $groupTours = $tours->filter(function ($tour) use ($cursor) {
                    return !empty($tour->tour_date)
                        && Carbon::parse($tour->tour_date)->format('Y-m') === $cursor->format('Y-m');
                });

                $cursor->addMonth();
            } else {
                $groupTours = $tours->filter(function ($tour) use ($cursor) {
                    return !empty($tour->tour_date)
                        && Carbon::parse($tour->tour_date)->toDateString() === $cursor->toDateString();
                });

                $cursor->addDay();
            }

            $labels[] = $label;
            $toursCount[] = $groupTours->count();
            $booked[] = $groupTours->sum(fn ($tour) => $this->bookedPeopleForTour($tour));
        }

        return [
            'labels' => $labels,
            'tours' => $toursCount,
            'booked' => $booked,
        ];
    }

    private function buildGuideTourTypeChart(Collection $tours): array
    {
        $grouped = $tours
            ->groupBy(fn ($tour) => optional($tour->tourType)->name ?? 'Okänd')
            ->map(fn ($group) => $group->count())
            ->sortDesc();

        return [
            'labels' => $grouped->keys()->values(),
            'values' => $grouped->values(),
        ];
    }

    private function buildGuideLanguageChart(Collection $tours): array
    {
        $share = $this->buildLanguageShare($tours);

        return [
            'labels' => $share->pluck('label')->values(),
            'values' => $share->pluck('count')->values(),
            'percentages' => $share->pluck('percent')->values(),
        ];
    }

    private function buildLanguageShare(Collection $tours): Collection
    {
        $languages = [];

        foreach ($tours as $tour) {
            foreach ($this->languageCodesForTour($tour) as $code) {
                $code = mb_strtolower($code);
                $languages[$code] = ($languages[$code] ?? 0) + 1;
            }
        }

        arsort($languages);

        $total = array_sum($languages);

        return collect($languages)->map(function ($count, $code) use ($total) {
            return [
                'label' => strtoupper($code),
                'count' => $count,
                'percent' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        })->values();
    }

    private function bookedPeopleForTour($tour): int
    {
        return (int) $tour->bookings
            ->filter(fn ($booking) => ($booking->status ?? null) !== 'cancelled')
            ->sum('total_count');
    }

    private function languageCodesForTour($tour): Collection
    {
        return $tour->bookings
            ->flatMap(fn ($booking) => $booking->languages->pluck('code'))
            ->filter()
            ->map(fn ($code) => strtoupper($code))
            ->unique()
            ->values();
    }

    private function resolveTourDurationMinutes($tour): int
    {
        if (!empty($tour->started_at) && !empty($tour->completed_at)) {
            $started = Carbon::parse($tour->started_at);
            $completed = Carbon::parse($tour->completed_at);

            $minutes = $started->diffInMinutes($completed, false);
            return $minutes > 0 ? $minutes : 0;
        }

        if (!empty($tour->started_at) && !empty($tour->tour_date) && !empty($tour->end_time)) {
            $started = Carbon::parse($tour->started_at);
            $plannedEnd = Carbon::parse($tour->tour_date)
                ->setTimeFromTimeString($this->normalizeTime($tour->end_time));

            $minutes = $started->diffInMinutes($plannedEnd, false);
            return $minutes > 0 ? $minutes : 0;
        }

        if (!empty($tour->start_time) && !empty($tour->end_time)) {
            $plannedStart = Carbon::createFromFormat('H:i:s', $this->normalizeTime($tour->start_time));
            $plannedEnd = Carbon::createFromFormat('H:i:s', $this->normalizeTime($tour->end_time));

            $minutes = $plannedStart->diffInMinutes($plannedEnd, false);
            return $minutes > 0 ? $minutes : 0;
        }

        return 0;
    }

    private function normalizeTime(?string $time): ?string
    {
        if (empty($time)) {
            return null;
        }

        $time = trim($time);

        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }

        return $time;
    }

    private function changePercent($current, $previous): int
    {
        if ((float) $previous === 0.0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

    private function emptyGuideStats(User $user): array
    {
        return [
            'guide_id' => $user->id,
            'guide_name' => $user->name,
            'tours_count' => 0,
            'booked_people' => 0,
            'avg_per_tour' => 0,
            'duration_minutes_total' => 0,
            'duration_tours_count' => 0,
            'avg_duration_minutes' => 0,
            'non_swedish_tours' => 0,
            'tour_type_counts' => collect(),
        ];
    }
}