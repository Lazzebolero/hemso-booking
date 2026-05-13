<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Tour;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now();
        $today = $now->toDateString();

        $ongoingTours = Tour::with(['guide', 'tourType', 'bookings'])
            ->whereDate('tour_date', $today)
            ->where('status', 'started')
            ->orderBy('start_time')
            ->get()
            ->map(function ($tour) {
                $activeBookings = $tour->bookings
                    ->whereNotIn('status', ['cancelled'])
                    ->where('is_waitlist', false);

                $tour->booked_people_count = (int) $activeBookings->sum('total_count');
                $tour->booking_groups_count = (int) $activeBookings->count();

                return $tour;
            });

        $todayTours = Tour::with(['guide', 'tourType', 'bookings'])
            ->whereDate('tour_date', $today)
            ->orderBy('start_time')
            ->get()
            ->map(function ($tour) {
                $activeBookings = $tour->bookings
                    ->whereNotIn('status', ['cancelled'])
                    ->where('is_waitlist', false);

                $tour->booked_people_count = (int) $activeBookings->sum('total_count');
                $tour->booking_groups_count = (int) $activeBookings->count();

                return $tour;
            });

        $upcomingTours = Tour::with(['guide', 'tourType', 'bookings.languages'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where(function ($query) use ($now) {
                $query->whereDate('tour_date', '>', $now->toDateString())
                    ->orWhere(function ($q) use ($now) {
                        $q->whereDate('tour_date', $now->toDateString())
                          ->whereTime('start_time', '>=', $now->format('H:i:s'));
                    });
            })
            ->orderBy('tour_date')
            ->orderBy('start_time')
            ->take(10)
            ->get()
            ->map(function ($tour) {
                $activeBookings = $tour->bookings
                    ->whereNotIn('status', ['cancelled'])
                    ->where('is_waitlist', false);

                $tour->booked_people_count = (int) $activeBookings->sum('total_count');
                $tour->booking_groups_count = (int) $activeBookings->count();

                return $tour;
            });

        $todayBookedPeople = (int) Booking::whereHas('tour', function ($query) use ($today) {
                $query->whereDate('tour_date', $today);
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

        return view('admin.dashboard', compact(
            'ongoingTours',
            'todayTours',
            'upcomingTours',
            'todayBookedPeople',
            'startedNotCompletedPeople',
            'startedToursCount',
            'totalPeopleToday'
        ));
    }
}