<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\WorkShift;

class QuickTourController extends Controller
{
   public function create()
{
    $quickTourDate = now()->toDateString();

    $guides = User::query()
        ->whereHas('roles', function ($query) {
            $query->where('slug', Roles::GUIDE);
        })
        ->with(['workShifts' => function ($query) use ($quickTourDate) {
            $query->whereDate('shift_date', $quickTourDate)
                ->where('shift_role', Roles::GUIDE)
                ->whereNotIn('status', ['cancelled'])
                ->orderBy('start_time');
        }])
        ->orderBy('name')
        ->get();

    $languages = Language::query()
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();

    $defaultLanguageIds = Language::query()
        ->where('code', 'sv')
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();

    if (empty($defaultLanguageIds)) {
        $defaultLanguageIds = Language::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    if (session('active_role') === Roles::GUIDE) {
        return view('guide.quick-tours.create', compact(
            'languages',
            'defaultLanguageIds'
        ));
    }

    return view('admin.quick-tours.create', compact(
        'guides',
        'languages',
        'defaultLanguageIds'
    ));
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
                ->withErrors(['men_count' => 'Du måste ange minst 1 deltagare totalt.'])
                ->withInput();
        }

        $now = now();

        $quickTourType = TourType::where('name', 'Snabbtur')->first();

        $guideId = $data['guide_id'] ?? null;

        if (auth()->user()->role === 'guide' && !$guideId) {
            $guideId = auth()->id();
        }

        $tour = Tour::create([
            'title' => 'Tur ' . $now->format('H:i'),
            'tour_type_id' => $quickTourType?->id,
            'description' => $data['notes'] ?? null,
            'tour_date' => $now->toDateString(),
            'start_time' => $now->format('H:i:s'),
            'end_time' => null,
            'max_participants' => max(25, $totalCount),
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

        return redirect()
            ->route('admin.tours.show', $tour)
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