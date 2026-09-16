<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingInflowService;
use App\Services\FerryBookingWaveService;
use App\Services\StatisticsAggregationService;
use App\Services\StatisticsVisitorWeatherService;
use App\Services\TourWaitOverThresholdReportService;
use App\Support\StatisticsPeriod;
use App\Support\StatisticsTourTypeFilter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class StatisticsController extends Controller
{
    public function __construct(
        private StatisticsVisitorWeatherService $visitorWeatherService,
        private BookingInflowService $bookingInflowService,
        private FerryBookingWaveService $ferryBookingWaveService,
        private TourWaitOverThresholdReportService $tourWaitOverThresholdReportService,
        private StatisticsAggregationService $statisticsAggregation,
    ) {}

    public function index(Request $request)
    {
        [$period, $date, $from, $to, $year, $month] = StatisticsPeriod::resolve($request);
        [$tourTypeId, $tourTypeLabel, $tourTypes, $tourTypeFilterValue] = StatisticsTourTypeFilter::resolve($request);

        $previousFrom = (clone $from)->subDays($from->diffInDays($to) + 1);
        $previousTo = (clone $from)->subDay();

        $stats = $this->statisticsAggregation->buildStats($from, $to, $tourTypeId, $tourTypeLabel);
        $comparison = $this->statisticsAggregation->buildStats($previousFrom, $previousTo, $tourTypeId, $tourTypeLabel);
        $dailyVisitorCounts = $this->statisticsAggregation->dailyVisitorCounts($year, $tourTypeId);
        $popularDays = $this->visitorWeatherService->attachWeatherToDays(
            collect($dailyVisitorCounts)->sortByDesc('booked')->take(10)->values()->all(),
        );
        $visitorWeatherExtremes = $this->visitorWeatherService->extremesFromDailyCounts($dailyVisitorCounts);

        $yearFrom = Carbon::create($year, 1, 1)->startOfYear();
        $yearTo = Carbon::create($year, 12, 31)->endOfYear();
        $yearCountriesBreakdown = $this->statisticsAggregation->countryBreakdownForRange($yearFrom, $yearTo, $tourTypeId);

        return view('admin.statistics.index', compact(
            'period',
            'date',
            'from',
            'to',
            'year',
            'month',
            'stats',
            'comparison',
            'previousFrom',
            'previousTo',
            'popularDays',
            'visitorWeatherExtremes',
            'tourTypes',
            'tourTypeFilterValue',
            'tourTypeLabel',
            'yearCountriesBreakdown',
        ));
    }

    public function bookingInflow(Request $request)
    {
        [$period, $date, $from, $to, $year, $month] = StatisticsPeriod::resolve($request);
        [$tourTypeId, $tourTypeLabel, $tourTypes, $tourTypeFilterValue] = StatisticsTourTypeFilter::resolve($request);

        $inflow = $this->bookingInflowService->build($from, $to, $tourTypeId);

        return view('admin.statistics.booking-inflow', compact(
            'period',
            'date',
            'from',
            'to',
            'year',
            'month',
            'tourTypes',
            'tourTypeFilterValue',
            'tourTypeLabel',
            'inflow',
        ));
    }

    public function ferryBookingWaves(Request $request)
    {
        [$period, $date, $from, $to, $year, $month] = StatisticsPeriod::resolve($request);
        [$tourTypeId, $tourTypeLabel, $tourTypes, $tourTypeFilterValue] = StatisticsTourTypeFilter::resolve($request);

        $waves = $this->ferryBookingWaveService->build($from, $to, $tourTypeId);

        return view('admin.statistics.ferry-booking-waves', compact(
            'period',
            'date',
            'from',
            'to',
            'year',
            'month',
            'tourTypes',
            'tourTypeFilterValue',
            'tourTypeLabel',
            'waves',
        ));
    }

    public function tourWaitTimes(Request $request)
    {
        [$period, $date, $from, $to, $year, $month] = StatisticsPeriod::resolve($request);
        [$tourTypeId, $tourTypeLabel, $tourTypes, $tourTypeFilterValue] = StatisticsTourTypeFilter::resolve($request);

        $report = $this->tourWaitOverThresholdReportService->build($from, $to, $tourTypeId);

        return view('admin.statistics.tour-wait-times', compact(
            'period',
            'date',
            'from',
            'to',
            'year',
            'month',
            'tourTypes',
            'tourTypeFilterValue',
            'tourTypeLabel',
            'report',
        ));
    }

    public function yearCountriesMap(Request $request)
    {
        $year = max(2000, min(2100, (int) $request->get('year', now()->year)));
        [$tourTypeId, $tourTypeLabel, $tourTypes, $tourTypeFilterValue] = StatisticsTourTypeFilter::resolve($request);

        $yearFrom = Carbon::create($year, 1, 1)->startOfYear();
        $yearTo = Carbon::create($year, 12, 31)->endOfYear();
        $yearCountriesBreakdown = $this->statisticsAggregation->countryBreakdownForRange($yearFrom, $yearTo, $tourTypeId);

        return view('admin.statistics.year-countries-map', compact(
            'year',
            'tourTypes',
            'tourTypeFilterValue',
            'tourTypeLabel',
            'yearCountriesBreakdown',
        ));
    }

    public function live(Request $request)
    {
        [$period, $date, $from, $to, $year, $month] = StatisticsPeriod::resolve($request);
        [$tourTypeId, $tourTypeLabel] = StatisticsTourTypeFilter::resolve($request);
        $stats = $this->statisticsAggregation->buildStats($from, $to, $tourTypeId, $tourTypeLabel);

        $yearFrom = Carbon::create($year, 1, 1)->startOfYear();
        $yearTo = Carbon::create($year, 12, 31)->endOfYear();

        return response()->json([
            'period' => $period,
            'date' => $date->toDateString(),
            'year' => $year,
            'month' => $month,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'stats' => $stats,
            'year_countries_breakdown' => $this->statisticsAggregation->countryBreakdownForRange($yearFrom, $yearTo, $tourTypeId),
        ]);
    }

    public function exportCsv(Request $request)
    {
        [, , $from, $to] = StatisticsPeriod::resolve($request);
        [$tourTypeId] = StatisticsTourTypeFilter::resolve($request);

        $rows = Booking::with(['tour', 'tour.guide', 'tour.tourType'])
            ->whereHas('tour', function ($query) use ($from, $to, $tourTypeId) {
                $this->applyTourDateRange($query, $from, $to);
                $this->applyTourTypeScope($query, $tourTypeId);
            })
            ->lazy();

        $filename = 'statistics-export-'.now()->format('Ymd-His').'.csv';

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, chr(0xEF).chr(0xBB).chr(0xBF));

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
                'Ospecificerade',
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
                    $row->unspecified_count ?? 0,
                    $row->status,
                    $row->arrival_status,
                ], ';');
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function applyTourTypeScope($query, ?int $tourTypeId): void
    {
        if ($tourTypeId !== null) {
            $query->where('tour_type_id', $tourTypeId);
        }
    }

    private function applyTourDateRange($query, Carbon $from, Carbon $to)
    {
        return $query
            ->where('tour_date', '>=', $from->toDateString())
            ->where('tour_date', '<', $to->copy()->addDay()->toDateString());
    }
}
