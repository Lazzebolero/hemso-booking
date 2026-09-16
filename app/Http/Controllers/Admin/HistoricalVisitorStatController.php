<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HistoricalVisitorComparisonService;
use App\Support\StatisticsPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoricalVisitorStatController extends Controller
{
    public function index(Request $request, HistoricalVisitorComparisonService $comparisonService): View
    {
        $baselineYears = $comparisonService->resolveBaselineYears();

        [$period, $date, $from, $to, $year, $month] = StatisticsPeriod::resolve($request);
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        $series = $comparisonService->dailySeries($from, $to, $baselineYears);
        $pastSeries = array_values(array_filter($series, fn (array $row) => $row['is_past']));
        $comparisonSummary = $comparisonService->summarizeComparison($series, $baselineYears);
        $forecastSummary = $comparisonService->summarizeForecast($series);

        $comparisonYear = (int) now()->year;
        $showChart = $request->boolean('chart', true);
        $chartData = $comparisonService->chartPayload($series, $baselineYears, $comparisonYear);

        return view('admin.statistics.historical-visitors', [
            'period' => $period,
            'date' => $date,
            'year' => $year,
            'month' => $month,
            'from' => $from,
            'to' => $to,
            'baselineYears' => $baselineYears,
            'comparisonYear' => $comparisonYear,
            'series' => $series,
            'pastSeries' => $pastSeries,
            'showChart' => $showChart,
            'chartData' => $chartData,
            'comparisonSummary' => $comparisonSummary,
            'forecastSummary' => $forecastSummary,
        ]);
    }
}
