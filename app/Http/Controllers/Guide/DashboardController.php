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

        $tours = Tour::with(['guide', 'tourType', 'bookings.languages'])
            ->where('guide_id', $user->id)
            ->whereDate('tour_date', '>=', now()->toDateString())
            ->whereNotIn('status', ['completed'])
            ->orderBy('tour_date')
            ->orderBy('start_time')
            ->get();

        return view('guide.dashboard', compact('tours'));
    }

    public function showTour(Tour $tour)
    {
        $this->ensureGuideOwnsTour($tour);

        $tour->load(['bookings']);

        $bookingCount = $tour->bookings()
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $bookedCount = $tour->bookings()
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_count');

        $availableSpots = max(0, $tour->max_participants - $bookedCount);
        $occupancyPercent = $tour->max_participants > 0
            ? round(($bookedCount / $tour->max_participants) * 100)
            : 0;

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

        $tour->update([
            'status' => 'started',
            'started_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        LogService::log('tour', $tour->id, 'started', null, [
            'status' => 'started',
            'started_at' => now(),
        ], 'Startade tur från guidesidan');

        return back()->with('success', 'Tur startad.');
    }

    public function completeTour(Tour $tour)
    {
        $this->ensureGuideOwnsTour($tour);

        $tour->update([
            'status' => 'completed',
            'ended_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        LogService::log('tour', $tour->id, 'completed', null, [
            'status' => 'completed',
            'ended_at' => now(),
        ], 'Avslutade tur från guidesidan');

        return back()->with('success', 'Tur avslutad.');
    }

    public function updateBookingParticipants(Booking $booking, Request $request)
    {
        $tour = $booking->tour;
        $this->ensureGuideOwnsTour($tour);

        if ($tour->status === 'completed') {
            return back()->withErrors(['booking' => 'Det går inte att ändra bokningar på en avslutad tur.']);
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

        $data['updated_by'] = auth()->id();

        $booking->update($data);

        LogService::log('booking', $booking->id, 'updated', null, $booking->fresh()->toArray(), 'Guide uppdaterade bokning');

        return redirect()
            ->route('guide.tours.show', $tour)
            ->with('success', 'Bokningen uppdaterades.');
    }

    protected function calculateTotal(array $data): int
    {
        return (int)($data['men_count'] ?? 0)
            + (int)($data['women_count'] ?? 0)
            + (int)($data['youth_count'] ?? 0)
            + (int)($data['child_count'] ?? 0);
    }

    protected function validateCapacity(Tour $tour, int $newTotal, ?int $ignoreBookingId = null): void
    {
        $existing = $tour->bookings()
            ->when($ignoreBookingId, fn($q) => $q->where('id', '!=', $ignoreBookingId))
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_count');

        if (($existing + $newTotal) > $tour->max_participants) {
            throw ValidationException::withMessages([
                'booking' => 'Bokningen överskrider max antal deltagare på turen.',
            ]);
        }
    }

    protected function ensureGuideOwnsTour(Tour $tour): void
    {
        if ((int) $tour->guide_id !== (int) auth()->id() && auth()->user()->role !== 'admin') {
            abort(403);
        }
    }
}
