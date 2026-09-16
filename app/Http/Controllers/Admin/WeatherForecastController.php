<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WeatherForecastService;
use Illuminate\View\View;

class WeatherForecastController extends Controller
{
    public function index(WeatherForecastService $forecastService): View
    {
        return view('admin.weather-forecast.index', [
            'forecast' => $forecastService->presentation(),
        ]);
    }
}
