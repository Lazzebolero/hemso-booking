<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Tour;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        [$period, $date, $from, $to] = $this->resolvePeriod($request);

        $previousFrom = (clone $from)->subDays($from->diffInDays($to) + 1);
        $previousTo = (clone $from)->subDay();

        $stats = $this->buildStats($from, $to, $period);
        $comparison = $this->buildStats($previousFrom, $previousTo, $period);

        return view('admin.statistics.index', compact(
            'period',
            'date',
            'from',
            'to',
            'stats',
            'comparison',
            'previousFrom',
            'previousTo'
        ));
    }

    public function live(Request $request)
    {
        [$period, $date, $from, $to] = $this->resolvePeriod($request);
        $stats = $this->buildStats($from, $to, $period);

        return response()->json([
            'period' => $period,
            'date' => $date->toDateString(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'stats' => $stats,
        ]);
    }

    public function exportCsv(Request $request)
    {
        [, , $from, $to] = $this->resolvePeriod($request);

        $rows = Booking::with(['tour', 'tour.guide', 'tour.tourType'])
            ->whereHas('tour', function ($query) use ($from, $to) {
                $query->whereBetween('tour_date', [$from->toDateString(), $to->toDateString()]);
            })
            ->get();

        $filename = 'statistics-export-' . now()->format('Ymd-His') . '.csv';

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Tur',
                'Turtyp',
                'Guide',
                'Datum',
                'Veckodag',
                'Bokade',
                'Män',
                'Kvinnor',
                'Ungdomar',
                'Barn',
                'Status',
                'Ankomststatus',
            ], ';');

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->tour?->title,
                    $row->tour?->tourType?->name ?? '-',
                    $row->tour?->guide?->name,
                    $row->tour?->tour_date,
                    $row->tour?->tour_date ? Carbon::parse($row->tour->tour_date)->locale('sv')->dayName : '',
                    $row->total_count,
                    $row->men_count ?? 0,
                    $row->women_count ?? 0,
                    $row->youth_count ?? 0,
                    $row->child_count ?? 0,
                    $row->status,
                    $row->arrival_status,
                ], ';');
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function resolvePeriod(Request $request): array
    {
        $period = $request->get('period', 'week');
        $date = Carbon::parse($request->get('date', now()->toDateString()));

        [$from, $to] = match ($period) {
            'day' => [(clone $date)->startOfDay(), (clone $date)->endOfDay()],
            'month' => [(clone $date)->startOfMonth(), (clone $date)->endOfMonth()],
            'year' => [(clone $date)->startOfYear(), (clone $date)->endOfYear()],
            default => [(clone $date)->startOfWeek(), (clone $date)->endOfWeek()],
        };

        return [$period, $date, $from, $to];
    }

    private function buildStats(Carbon $from, Carbon $to, string $period): array
    {
        $tours = Tour::with(['guide', 'tourType', 'bookings'])
            ->whereBetween('tour_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $bookings = Booking::with(['tour', 'tour.guide', 'tour.tourType'])
            ->whereHas('tour', function ($query) use ($from, $to) {
                $query->whereBetween('tour_date', [$from->toDateString(), $to->toDateString()]);
            })
            ->get();

        $activeBookings = $bookings
            ->where('is_waitlist', false)
            ->whereNotIn('status', ['cancelled']);

        $menCount = (int) $activeBookings->sum(fn ($booking) => (int) ($booking->men_count ?? 0));
        $womenCount = (int) $activeBookings->sum(fn ($booking) => (int) ($booking->women_count ?? 0));
        $youthCount = (int) $activeBookings->sum(fn ($booking) => (int) ($booking->youth_count ?? 0));
        $childCount = (int) $activeBookings->sum(fn ($booking) => (int) ($booking->child_count ?? 0));

        $summary = [
            'tours' => (int) $tours->count(),
            'bookings' => (int) $bookings->count(),
            'booked_people' => (int) $activeBookings->sum('total_count'),
            'cancelled' => (int) $bookings->where('status', 'cancelled')->count(),
            'waitlist' => (int) $bookings->where('is_waitlist', true)->count(),
            'late_cancel' => (int) $bookings->where('arrival_status', 'late_cancel')->count(),
            'no_show' => (int) $bookings->where('arrival_status', 'no_show')->count(),
            'occupancy_rate' => $tours->sum('max_participants') > 0
                ? round(($activeBookings->sum('total_count') / $tours->sum('max_participants')) * 100)
                : 0,

            'men_count' => $menCount,
            'women_count' => $womenCount,
            'youth_count' => $youthCount,
            'child_count' => $childCount,
        ];

        $distribution = [
            'Män' => $summary['men_count'],
            'Kvinnor' => $summary['women_count'],
            'Ungdomar' => $summary['youth_count'],
            'Barn' => $summary['child_count'],
        ];

        $timeline = $this->buildTimeline($from, $to, $period);
        $tourTypeSummary = $this->buildTourTypeSummary($tours);
        $tourTypeTimeline = $this->buildTourTypeTimeline($from, $to, $period);

        $occupancyByTourType = collect($tourTypeSummary)
            ->map(fn ($row) => [
                'label' => $row['label'],
                'capacity' => $row['capacity'],
                'booked' => $row['booked_people'],
                'occupancy_percent' => $row['occupancy_percent'],
            ])
            ->values()
            ->all();

        $occupancyByWeekday = $this->buildOccupancy(
            $tours->groupBy(fn ($tour) => Carbon::parse($tour->tour_date)->locale('sv')->dayName)
        );

        $occupancyByGuide = $this->buildOccupancy(
            $tours->groupBy(fn ($tour) => $tour->guide?->name ?: 'Ej tilldelad')
        );

        $popularTimes = $tours->groupBy('start_time')->map(function ($items, $label) {
            return [
                'label' => $label,
                'bookings' => (int) $items->sum(fn ($tour) =>
                    $tour->bookings
                        ->where('is_waitlist', false)
                        ->whereNotIn('status', ['cancelled'])
                        ->count()
                ),
                'booked_people' => (int) $items->sum(fn ($tour) =>
                    $tour->bookings
                        ->where('is_waitlist', false)
                        ->whereNotIn('status', ['cancelled'])
                        ->sum('total_count')
                ),
            ];
        })->sortByDesc('booked_people')->values()->all();

        $tourTypeComparison = collect($tourTypeSummary)->sortByDesc('booked_people')->values()->all();

        $topTourType = collect($tourTypeSummary)->sortByDesc('booked_people')->first();
        $bestOccupancyTourType = collect($tourTypeSummary)->sortByDesc('occupancy_percent')->first();
        $largestAverageTourType = collect($tourTypeSummary)->sortByDesc('avg_group_size')->first();

        return [
            'summary' => $summary,
            'distribution' => $distribution,
            'timeline' => $timeline,
            'day_summary' => [
                'booked' => $summary['booked_people'],
                'late_cancel' => $summary['late_cancel'],
                'no_show' => $summary['no_show'],
                'cancelled' => $summary['cancelled'],
            ],
            'occupancy_by_tour_type' => $occupancyByTourType,
            'occupancy_by_weekday' => $occupancyByWeekday,
            'occupancy_by_guide' => $occupancyByGuide,
            'popular_times' => $popularTimes,

            'tour_type_summary' => $tourTypeSummary,
            'tour_type_comparison' => $tourTypeComparison,
            'tour_type_timeline' => $tourTypeTimeline,

            'tour_type_insights' => [
                'top_booked' => $topTourType,
                'best_occupancy' => $bestOccupancyTourType,
                'largest_average' => $largestAverageTourType,
            ],
        ];
    }

    private function buildOccupancy($grouped): array
    {
        return $grouped->map(function ($items, $label) {
            $capacity = $items->sum('max_participants');

            $booked = $items->sum(function ($tour) {
                return $tour->bookings
                    ->where('is_waitlist', false)
                    ->whereNotIn('status', ['cancelled'])
                    ->sum('total_count');
            });

            return [
                'label' => $label,
                'capacity' => $capacity,
                'booked' => $booked,
                'occupancy_percent' => $capacity > 0 ? round(($booked / $capacity) * 100, 1) : 0,
            ];
        })->values()->all();
    }

    private function buildTourTypeSummary($tours): array
    {
        return $tours
            ->groupBy(fn ($tour) => $tour->tourType?->name ?? 'Ej angiven')
            ->map(function ($items, $label) {
                $capacity = (int) $items->sum('max_participants');

                $bookedPeople = (int) $items->sum(function ($tour) {
                    return $tour->bookings
                        ->where('is_waitlist', false)
                        ->whereNotIn('status', ['cancelled'])
                        ->sum('total_count');
                });

                $tourCount = (int) $items->count();

                return [
                    'label' => $label,
                    'tours' => $tourCount,
                    'capacity' => $capacity,
                    'booked_people' => $bookedPeople,
                    'avg_group_size' => $tourCount > 0 ? round($bookedPeople / $tourCount, 1) : 0,
                    'occupancy_percent' => $capacity > 0 ? round(($bookedPeople / $capacity) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('booked_people')
            ->values()
            ->all();
    }

    private function buildTourTypeTimeline(Carbon $from, Carbon $to, string $period): array
    {
        $tourTypes = Tour::with('tourType')
            ->whereBetween('tour_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->map(fn ($tour) => $tour->tourType?->name ?? 'Ej angiven')
            ->unique()
            ->values();

        $labels = [];
        $buckets = [];

        if ($period === 'year') {
            $cursor = (clone $from)->startOfMonth();

            while ($cursor <= $to) {
                $start = (clone $cursor)->startOfMonth();
                $end = (clone $cursor)->endOfMonth();

                $labels[] = $cursor->translatedFormat('M');
                $buckets[] = [$start, $end];

                $cursor->addMonth();
            }
        } elseif ($period === 'month') {
            $cursor = (clone $from)->startOfWeek();

            while ($cursor <= $to) {
                $start = (clone $cursor)->startOfWeek();
                $end = (clone $cursor)->endOfWeek();

                $labels[] = 'v.' . $cursor->weekOfYear;
                $buckets[] = [$start, $end];

                $cursor->addWeek();
            }
        } else {
            $cursor = (clone $from)->startOfDay();

            while ($cursor <= $to) {
                $start = (clone $cursor)->startOfDay();
                $end = (clone $cursor)->endOfDay();

                $labels[] = $period === 'day'
                    ? $cursor->translatedFormat('d/m')
                    : $cursor->translatedFormat('D d/m');

                $buckets[] = [$start, $end];

                $cursor->addDay();
            }
        }

        $series = $tourTypes->map(function ($tourTypeName) use ($buckets) {
            $data = collect($buckets)->map(function ($bucket) use ($tourTypeName) {
                [$start, $end] = $bucket;

                return (int) Booking::whereHas('tour', function ($query) use ($start, $end, $tourTypeName) {
                    $query->whereBetween('tour_date', [$start->toDateString(), $end->toDateString()])
                        ->whereHas('tourType', function ($typeQuery) use ($tourTypeName) {
                            $typeQuery->where('name', $tourTypeName);
                        });
                })
                    ->where('is_waitlist', false)
                    ->whereNotIn('status', ['cancelled'])
                    ->sum('total_count');
            })->values()->all();

            return [
                'label' => $tourTypeName,
                'data' => $data,
            ];
        })->values()->all();

        return [
            'labels' => $labels,
            'series' => $series,
        ];
    }

    private function buildTimeline(Carbon $from, Carbon $to, string $period): array
    {
        $labels = [];
        $booked = [];
        $tours = [];

        if ($period === 'year') {
            $cursor = (clone $from)->startOfMonth();

            while ($cursor <= $to) {
                $start = (clone $cursor)->startOfMonth();
                $end = (clone $cursor)->endOfMonth();

                $labels[] = $cursor->translatedFormat('M');
                $booked[] = $this->sumBookedBetween($start, $end);
                $tours[] = $this->countToursBetween($start, $end);

                $cursor->addMonth();
            }
        } elseif ($period === 'month') {
            $cursor = (clone $from)->startOfWeek();

            while ($cursor <= $to) {
                $start = (clone $cursor)->startOfWeek();
                $end = (clone $cursor)->endOfWeek();

                $labels[] = 'v.' . $cursor->weekOfYear;
                $booked[] = $this->sumBookedBetween($start, $end);
                $tours[] = $this->countToursBetween($start, $end);

                $cursor->addWeek();
            }
        } else {
            $cursor = (clone $from)->startOfDay();

            while ($cursor <= $to) {
                $start = (clone $cursor)->startOfDay();
                $end = (clone $cursor)->endOfDay();

                $labels[] = $period === 'day'
                    ? $cursor->translatedFormat('d/m')
                    : $cursor->translatedFormat('D d/m');

                $booked[] = $this->sumBookedBetween($start, $end);
                $tours[] = $this->countToursBetween($start, $end);

                $cursor->addDay();
            }
        }

        return [
            'labels' => $labels,
            'booked' => $booked,
            'tours' => $tours,
        ];
    }

    private function sumBookedBetween(Carbon $from, Carbon $to): int
    {
        return (int) Booking::whereHas('tour', function ($query) use ($from, $to) {
            $query->whereBetween('tour_date', [$from->toDateString(), $to->toDateString()]);
        })
            ->where('is_waitlist', false)
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_count');
    }

    private function countToursBetween(Carbon $from, Carbon $to): int
    {
        return (int) Tour::whereBetween('tour_date', [$from->toDateString(), $to->toDateString()])->count();
    }
}