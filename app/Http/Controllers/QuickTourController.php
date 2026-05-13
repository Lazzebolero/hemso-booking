<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuickTourController extends Controller
{
    public function create()
    {
        $guides = User::where('role', 'guide')
            ->orderBy('name')
            ->get();

        return view('quick-tours.create', compact('guides'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'men_count' => ['required', 'integer', 'min:0'],
            'women_count' => ['required', 'integer', 'min:0'],
            'youth_count' => ['required', 'integer', 'min:0'],
            'child_count' => ['required', 'integer', 'min:0'],
            'guide_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $totalCount =
            (int) $data['men_count'] +
            (int) $data['women_count'] +
            (int) $data['youth_count'] +
            (int) $data['child_count'];

        if ($totalCount <= 0) {
            return back()
                ->withErrors([
                    'men_count' => 'Du måste ange minst 1 deltagare totalt.',
                ])
                ->withInput();
        }

        $now = now();
        $quickTourType = TourType::where('name', 'Snabbtur')->first();

        $guideId = $data['guide_id'] ?? null;

        if (auth()->user()->role === 'guide' && !$guideId) {
            $guideId = auth()->id();
        }

        $tour = Tour::create([
            'title' => 'Snabbtur ' . $now->format('Y-m-d H:i'),
            'tour_type_id' => $quickTourType?->id,
            'description' => $data['notes'] ?? null,
            'tour_date' => $now->toDateString(),
            'start_time' => $now->format('H:i:s'),
            'end_time' => null,
            'max_participants' => max((int) setting('default_tour_capacity', 25), $totalCount),
            'guide_id' => $guideId,
            'status' => 'started',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'started_at' => $now,
        ]);

        $booking = Booking::create([
            'tour_id' => $tour->id,
            'booking_name' => $this->generateBookingName(),
            'contact_name' => null,
            'phone' => null,
            'email' => null,
            'men_count' => (int) $data['men_count'],
            'women_count' => (int) $data['women_count'],
            'youth_count' => (int) $data['youth_count'],
            'child_count' => (int) $data['child_count'],
            'total_count' => $totalCount,
            'notes' => $data['notes'] ?? 'Skapad via snabbtur',
            'status' => 'confirmed',
            'arrival_status' => 'booked',
            'is_walk_in' => true,
            'is_waitlist' => false,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        if (class_exists(LogService::class)) {
            LogService::log('tour', $tour->id, 'created', null, $tour->toArray(), 'Skapade snabbtur');
            LogService::log('booking', $booking->id, 'created', null, $booking->toArray(), 'Skapade snabbgrupp');
        }

        $route = auth()->user()->role === 'guide'
            ? 'guide.tours.show'
            : 'admin.tours.show';

        return redirect()
            ->route($route, $tour)
            ->with('success', 'Snabbtur skapad och startad.');
    }

    private function generateBookingName(): string
    {
        do {
            $candidate = 'BOK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Booking::where('booking_name', $candidate)->exists());

        return $candidate;
    }
}