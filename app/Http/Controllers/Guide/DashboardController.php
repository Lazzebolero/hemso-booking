<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Tour;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = now()->toDateString();
        $nowTime = now()->format('H:i:s');

        $todayTours = Tour::with([
            'guide',
            'tourType',
            'bookings.languages',
        ])
            ->where('guide_id', $user->id)
            ->whereDate('tour_date', $today)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_time')
            ->get()
            ->map(fn (Tour $tour) => $this->decorateTour($tour));

        $ongoingTour = Tour::with([
            'guide',
            'tourType',
            'bookings.languages',
        ])
            ->where('guide_id', $user->id)
            ->where('status', 'started')
            ->orderByDesc('started_at')
            ->orderByDesc('tour_date')
            ->orderByDesc('start_time')
            ->first();

        if ($ongoingTour) {
            $ongoingTour = $this->decorateTour($ongoingTour);
        }

        $upcomingTours = Tour::with([
            'guide',
            'tourType',
            'bookings.languages',
        ])
            ->where('guide_id', $user->id)
            ->where('status', 'planned')
            ->where(function ($query) use ($today, $nowTime) {
                $query->whereDate('tour_date', '>', $today)
                    ->orWhere(function ($q) use ($today, $nowTime) {
                        $q->whereDate('tour_date', $today)
                            ->whereTime('start_time', '>=', $nowTime);
                    });
            })
            ->orderBy('tour_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (Tour $tour) => $this->decorateTour($tour));

        $nextTour = $upcomingTours->first();

        $upcomingTourCount = (int) $upcomingTours->count();
        $upcomingParticipantCount = (int) $upcomingTours->sum('booked_people_count');

        return view('guide.dashboard', compact(
            'todayTours',
            'ongoingTour',
            'upcomingTours',
            'nextTour',
            'upcomingTourCount',
            'upcomingParticipantCount'
        ));
    }

    public function showTour(Tour $tour)
    {
        $this->ensureGuideOwnsTour($tour);

        $tour->load([
            'guide',
            'tourType',
            'bookings.languages',
            'photos.uploader',
        ]);

        $tour = $this->decorateTour($tour);

        $bookingCount = $tour->booking_groups_count;
        $bookedCount = $tour->booked_people_count;
        $availableSpots = max(0, (int) $tour->max_participants - $bookedCount);
        $occupancyPercent = (int) ($tour->occupancy_percent ?? 0);

        return view('guide.tour-show', compact(
            'tour',
            'bookingCount',
            'bookedCount',
            'availableSpots',
            'occupancyPercent'
        ));
    }

    public function startTour(Tour $tour)
    {
        $this->ensureGuideOwnsTour($tour);

        if ($tour->status === 'completed') {
            return back()->withErrors([
                'tour' => 'Det går inte att starta en redan avslutad tur.',
            ]);
        }

        $old = $tour->toArray();

        $tour->update([
            'status' => 'started',
            'started_at' => $tour->started_at ?: now(),
            'updated_by' => auth()->id(),
        ]);

        if (class_exists(LogService::class)) {
            LogService::log(
                'tour',
                $tour->id,
                'started',
                $old,
                $tour->fresh()->toArray(),
                'Startade tur från guidevyn'
            );
        }

        return back()->with('success', 'Tur startad.');
    }

    public function completeTour(Tour $tour)
    {
        $this->ensureGuideOwnsTour($tour);

        if ($tour->status === 'completed') {
            return back()->withErrors([
                'tour' => 'Turen är redan avslutad.',
            ]);
        }

        $old = $tour->toArray();

        $tour->update([
            'status' => 'completed',
            'ended_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        if (class_exists(LogService::class)) {
            LogService::log(
                'tour',
                $tour->id,
                'completed',
                $old,
                $tour->fresh()->toArray(),
                'Avslutade tur från guidevyn'
            );
        }

        return back()->with('success', 'Tur avslutad.');
    }

    public function updateBookingParticipants(Booking $booking, Request $request)
    {
        $tour = $booking->tour;
        $this->ensureGuideOwnsTour($tour);

        if ($tour->status === 'completed') {
            return back()->withErrors([
                'booking' => 'Det går inte att ändra bokningar på en avslutad tur.',
            ]);
        }

        $data = $request->validate([
            'men_count' => ['required', 'integer', 'min:0'],
            'women_count' => ['required', 'integer', 'min:0'],
            'youth_count' => ['required', 'integer', 'min:0'],
            'child_count' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:preliminary,confirmed,cancelled,completed'],
        ]);

        $data['total_count'] = $this->calculateTotal($data);

        $this->validateCapacity($tour, $data['total_count'], $booking->id);

        $old = $booking->toArray();

        $data['updated_by'] = auth()->id();
        $booking->update($data);

        if (class_exists(LogService::class)) {
            LogService::log(
                'booking',
                $booking->id,
                'updated',
                $old,
                $booking->fresh()->toArray(),
                'Guide uppdaterade bokning'
            );
        }

        return redirect()
            ->route('guide.tours.show', $tour)
            ->with('success', 'Bokningen uppdaterades.');
    }

    protected function decorateTour(Tour $tour): Tour
    {
        $activeBookings = collect($tour->bookings ?? [])
            ->whereNotIn('status', ['cancelled'])
            ->where('is_waitlist', false);

        $tour->booked_people_count = (int) $activeBookings->sum('total_count');
        $tour->booking_groups_count = (int) $activeBookings->count();
        $tour->occupancy_percent = (int) (
            ($tour->max_participants ?? 0) > 0
                ? round(($tour->booked_people_count / $tour->max_participants) * 100)
                : 0
        );

        return $tour;
    }

    protected function calculateTotal(array $data): int
    {
        return (int) ($data['men_count'] ?? 0)
            + (int) ($data['women_count'] ?? 0)
            + (int) ($data['youth_count'] ?? 0)
            + (int) ($data['child_count'] ?? 0);
    }

    protected function validateCapacity(Tour $tour, int $newTotal, ?int $ignoreBookingId = null): void
    {
        $existing = $tour->bookings()
            ->when($ignoreBookingId, fn ($query) => $query->where('id', '!=', $ignoreBookingId))
            ->whereNotIn('status', ['cancelled'])
            ->where('is_waitlist', false)
            ->sum('total_count');

        if (($existing + $newTotal) > (int) $tour->max_participants) {
            throw ValidationException::withMessages([
                'booking' => 'Bokningen överskrider max antal deltagare på turen.',
            ]);
        }
    }

    protected function ensureGuideOwnsTour(Tour $tour): void
    {
        if ((int) $tour->guide_id !== (int) auth()->id()) {
            abort(403);
        }
    }
}
