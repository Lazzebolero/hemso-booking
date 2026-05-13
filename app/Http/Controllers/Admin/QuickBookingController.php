<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Language;
use App\Models\Tour;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuickBookingController extends Controller
{
   public function create()
{
    $today = now()->toDateString();
    $nowTime = now()->format('H:i:s');

    $tours = \App\Models\Tour::with([
            'guide',
            'tourType',
            'bookings.languages',
        ])
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
        ->get();

    $preferredTourId = optional($tours->first())->id;

    $languages = \App\Models\Language::query()
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();

    $defaultLanguageId = \App\Models\Language::query()
        ->where('code', 'sv')
        ->value('id');

    if (!$defaultLanguageId) {
        $defaultLanguageId = optional($languages->first())->id;
    }

    return view('admin.bookings.quick-create', compact(
        'tours',
        'preferredTourId',
        'languages',
        'defaultLanguageId'
    ));
}

    public function store(Request $request)
    {
        $data = $request->validate([
            'tour_id' => ['required', 'exists:tours,id'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'men_count' => ['required', 'integer', 'min:0'],
            'women_count' => ['required', 'integer', 'min:0'],
            'youth_count' => ['required', 'integer', 'min:0'],
            'child_count' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['exists:languages,id'],
        ]);

        $tour = Tour::with('bookings')->findOrFail($data['tour_id']);

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

        $currentBooked = (int) $tour->bookings()
            ->whereNotIn('status', ['cancelled'])
            ->where('is_waitlist', false)
            ->sum('total_count');

        $isWaitlist = ($currentBooked + $totalCount) > $tour->max_participants;

        $booking = Booking::create([
            'tour_id' => $tour->id,
            'booking_name' => $this->generateBookingName(),
            'contact_name' => $data['contact_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'men_count' => (int) $data['men_count'],
            'women_count' => (int) $data['women_count'],
            'youth_count' => (int) $data['youth_count'],
            'child_count' => (int) $data['child_count'],
            'total_count' => $totalCount,
            'notes' => $data['notes'] ?? null,
            'status' => 'confirmed',
            'arrival_status' => 'booked',
            'is_waitlist' => $isWaitlist,
            'is_walk_in' => false,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $languageIds = $request->input('languages', []);
        if (empty($languageIds)) {
            $defaultLanguageId = Language::where('is_default', true)->value('id');
            if ($defaultLanguageId) {
                $languageIds = [$defaultLanguageId];
            }
        }
        $booking->languages()->sync($languageIds);

        LogService::log(
            'booking',
            $booking->id,
            'created',
            null,
            $booking->fresh(['languages'])->toArray(),
            'Skapade bokning via snabbbokning'
        );

        return redirect()
            ->route('admin.bookings.quick-create')
            ->with('success', $isWaitlist ? 'Bokning skapad i väntelista.' : 'Bokning skapad.');
    }

    private function findBestTourId($tours): ?int
    {
        foreach ($tours as $tour) {
            $booked = $tour->bookings
                ->where('status', '!=', 'cancelled')
                ->where('is_waitlist', false)
                ->sum('total_count');

            if ($booked < $tour->max_participants) {
                return $tour->id;
            }
        }

        return $tours->first()?->id;
    }

    private function generateBookingName(): string
    {
        do {
            $candidate = 'BOK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
        } while (Booking::where('booking_name', $candidate)->exists());

        return $candidate;
    }
}