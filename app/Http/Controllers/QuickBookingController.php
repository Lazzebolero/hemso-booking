<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Language;
use App\Models\Tour;
use App\Services\BookingParticipantService;
use App\Services\GuideLanguageMatchService;
use App\Services\LogService;
use App\Support\ActiveRole;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuickBookingController extends Controller
{
    public function __construct(
        private BookingParticipantService $participants,
        private GuideLanguageMatchService $guideLanguageMatchService,
    ) {}

    public function create()
    {
        $today = now()->toDateString();
        $nowTime = now()->format('H:i:s');

        $tours = Tour::with([
            'guide',
            'tourType',
            'bookings.languages',
        ])
            ->eligibleForBookingSequence()
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

        $languages = Language::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $defaultLanguageId = Language::query()
            ->where('code', 'sv')
            ->value('id');

        if (! $defaultLanguageId) {
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
            'participant_count' => ['nullable', 'integer', 'min:1'],
            'men_count' => ['nullable', 'integer', 'min:0'],
            'women_count' => ['nullable', 'integer', 'min:0'],
            'youth_count' => ['nullable', 'integer', 'min:0'],
            'child_count' => ['nullable', 'integer', 'min:0'],
            'unspecified_count' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['exists:languages,id'],
            'includes_meal' => ['nullable', 'boolean'],
        ]);

        $tour = Tour::query()
            ->eligibleForBookingSequence()
            ->with('bookings')
            ->findOrFail($data['tour_id']);

        $counts = $this->participants->normalize($data);
        $totalCount = $counts['total_count'];

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
            'men_count' => $counts['men_count'],
            'women_count' => $counts['women_count'],
            'youth_count' => $counts['youth_count'],
            'child_count' => $counts['child_count'],
            'unspecified_count' => $counts['unspecified_count'],
            'total_count' => $counts['total_count'],
            'notes' => $data['notes'] ?? null,
            'status' => 'confirmed',
            'arrival_status' => 'booked',
            'is_waitlist' => $isWaitlist,
            'is_walk_in' => false,
            'includes_meal' => (string) $request->input('includes_meal', '0') === '1',
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
            'Skapade bokning via bokningssekvens'
        );

        $redirect = redirect()
            ->route(ActiveRole::routePrefix().'.bookings.quick-create')
            ->with('success', $isWaitlist ? 'Bokning skapad i väntelista.' : 'Bokning skapad.');

        $warning = $this->guideLanguageMatchService->bookingGuideLanguageWarning(
            $tour->fresh(['guide.guideLanguages']),
            $this->languageCodesFromIds($languageIds)
        );

        return $warning ? $redirect->with('warning', $warning) : $redirect;
    }

    /**
     * @param  list<int|string>  $languageIds
     * @return list<string>
     */
    private function languageCodesFromIds(array $languageIds): array
    {
        if ($languageIds === []) {
            return [];
        }

        return Language::query()
            ->whereIn('id', $languageIds)
            ->pluck('code')
            ->all();
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
            $candidate = 'BOK-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));
        } while (Booking::where('booking_name', $candidate)->exists());

        return $candidate;
    }
}
