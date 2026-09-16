<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EconomyRestaurantCostService;
use App\Services\EconomySettingsService;
use App\Support\StatisticsPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RestaurantEconomyCostController extends Controller
{
    public function __construct(
        private EconomyRestaurantCostService $restaurantCost,
        private EconomySettingsService $economySettings,
    ) {}

    public function index(Request $request): View
    {
        [$period, $date, $from, $to, $year, $month] = StatisticsPeriod::resolve($request);

        $report = $this->restaurantCost->forPeriod($from, $to);

        return view('admin.economy.restaurant-cost', [
            'period' => $period,
            'date' => $date,
            'from' => $from,
            'to' => $to,
            'year' => $year,
            'month' => $month,
            'days' => $report['days'],
            'totals' => $report['totals'],
            'prices' => $this->economySettings->all(),
        ]);
    }
}
