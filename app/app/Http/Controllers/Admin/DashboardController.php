<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\FacilityReport;
use App\Models\Tour;

class DashboardController extends Controller
{
    public function index()
    {
        $upcomingTours = Tour::with('guide')->whereDate('tour_date', '>=', now()->toDateString())->orderBy('tour_date')->orderBy('start_time')->take(10)->get();

        return view('admin.dashboard', [
            'upcomingTours' => $upcomingTours,
            'tourCount' => Tour::count(),
            'bookingCount' => Booking::count(),
            'openReportCount' => FacilityReport::whereNotIn('status', ['resolved', 'closed'])->count(),
        ]);
    }
}
