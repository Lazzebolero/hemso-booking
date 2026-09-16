<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class UnspecifiedFollowUpController extends Controller
{
    public function index(Request $request): View
    {
        $from = Carbon::parse($request->get('from', now()->startOfWeek(Carbon::MONDAY)->toDateString()))->startOfDay();
        $to = Carbon::parse($request->get('to', now()->endOfWeek(Carbon::SUNDAY)->toDateString()))->startOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        $scope = $request->get('scope', 'upcoming');
        if (! in_array($scope, ['all', 'upcoming', 'completed'], true)) {
            $scope = 'upcoming';
        }

        $baseQuery = Booking::query()
            ->where('unspecified_count', '>', 0)
            ->where('status', '!=', 'cancelled')
            ->where('is_waitlist', false)
            ->whereHas('tour', function ($tourQuery) use ($from, $to, $scope) {
                $tourQuery
                    ->whereDate('tour_date', '>=', $from->toDateString())
                    ->whereDate('tour_date', '<=', $to->toDateString());

                if ($scope === 'upcoming') {
                    $tourQuery->where(function ($query) {
                        $query->whereDate('tour_date', '>', now()->toDateString())
                            ->orWhere(function ($todayQuery) {
                                $todayQuery->whereDate('tour_date', now()->toDateString())
                                    ->whereNotIn('status', ['completed', 'cancelled']);
                            });
                    });
                } elseif ($scope === 'completed') {
                    $tourQuery->where(function ($query) {
                        $query->whereDate('tour_date', '<', now()->toDateString())
                            ->orWhere('status', 'completed');
                    });
                }
            });

        $summary = [
            'unspecified_people' => (int) (clone $baseQuery)->sum('unspecified_count'),
            'booking_count' => (int) (clone $baseQuery)->count(),
            'tour_count' => (int) (clone $baseQuery)->distinct()->count('tour_id'),
        ];

        $bookings = (clone $baseQuery)
            ->with(['tour.guide', 'tour.tourType'])
            ->latest('bookings.id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.statistics.unspecified-follow-up', compact(
            'from',
            'to',
            'scope',
            'summary',
            'bookings',
        ));
    }
}
