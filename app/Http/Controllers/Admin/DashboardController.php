<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Tour;
use App\Services\FacilityReportAlertService;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now();
        $today = $now->toDateString();
        $lateTime = $now->copy()->subMinutes(10)->format('H:i:s');

        $todayTours = Tour::with([
            'guide',
            'tourType',
            'bookings.languages',
        ])
            ->whereDate('tour_date', $today)
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->get()
            ->map(fn (Tour $tour) => $this->decorateTour($tour));

        $ongoingTours = Tour::with([
            'guide',
            'tourType',
            'bookings.languages',
        ])
            ->whereDate('tour_date', $today)
            ->where('status', 'started')
            ->orderBy('start_time')
            ->get()
            ->map(fn (Tour $tour) => $this->decorateTour($tour));

        $upcomingTours = Tour::with([
            'guide',
            'tourType',
            'bookings.languages',
        ])
            ->where('status', 'planned')
            ->where(function ($query) use ($today, $now) {
                $query->whereDate('tour_date', '>', $today)
                    ->orWhere(function ($q) use ($today, $now) {
                        $q->whereDate('tour_date', $today)
                            ->whereTime('start_time', '>=', $now->format('H:i:s'));
                    });
            })
            ->orderBy('tour_date')
            ->orderBy('start_time')
            ->take(10)
            ->get()
            ->map(fn (Tour $tour) => $this->decorateTour($tour));

        $lateUnstartedTours = Tour::with([
            'guide',
            'tourType',
            'bookings.languages',
        ])
            ->where('status', 'planned')
            ->where(function ($query) use ($today, $lateTime) {
                $query->whereDate('tour_date', '<', $today)
                    ->orWhere(function ($q) use ($today, $lateTime) {
                        $q->whereDate('tour_date', $today)
                            ->whereTime('start_time', '<', $lateTime);
                    });
            })
            ->orderByDesc('tour_date')
            ->orderBy('start_time')
            ->take(10)
            ->get()
            ->map(fn (Tour $tour) => $this->decorateTour($tour));

        $todayBookedPeople = (int) Booking::whereHas('tour', function ($query) use ($today) {
            $query->whereDate('tour_date', $today)
                ->where('status', '!=', 'cancelled');
        })
            ->whereNotIn('status', ['cancelled'])
            ->where('is_waitlist', false)
            ->sum('total_count');

        $startedNotCompletedPeople = (int) Booking::whereHas('tour', function ($query) use ($today) {
            $query->whereDate('tour_date', $today)
                ->where('status', 'started');
        })
            ->whereNotIn('status', ['cancelled'])
            ->where('is_waitlist', false)
            ->sum('total_count');

        $startedToursCount = (int) Tour::whereDate('tour_date', $today)
            ->where('status', 'started')
            ->count();

        $totalPeopleToday = $todayBookedPeople;
        $nextTour = $upcomingTours->first();

        $newOpenFacilityReportsCount = FacilityReportAlertService::countNewOpenSinceAcknowledgmentForUser(auth()->user());

        return view('admin.dashboard', compact(
            'todayTours',
            'ongoingTours',
            'upcomingTours',
            'lateUnstartedTours',
            'todayBookedPeople',
            'startedNotCompletedPeople',
            'startedToursCount',
            'totalPeopleToday',
            'nextTour',
            'newOpenFacilityReportsCount'
        ));
    }

    protected function decorateTour(Tour $tour): Tour
    {
        $activeBookings = collect($tour->bookings ?? [])
            ->whereNotIn('status', ['cancelled'])
            ->where('is_waitlist', false);

        $tour->booked_people_count = (int) $activeBookings->sum('total_count');
        $tour->booking_groups_count = (int) $activeBookings->count();

        return $tour;
    }
}
