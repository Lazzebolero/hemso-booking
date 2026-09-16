<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Tour;
use App\Services\BookingParticipantService;
use App\Services\LogService;
use App\Services\OpeningCheckService;
use App\Services\TourAutoCompleteService;
use App\Services\TourCoGuideService;
use App\Services\TourHeadcountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __construct(
        private BookingParticipantService $participants,
        private TourHeadcountService $headcount,
        private TourAutoCompleteService $autoComplete,
        private TourCoGuideService $tourCoGuideService,
        private OpeningCheckService $openingChecks,
    ) {}

    public function index()
    {
        $user = auth()->user();

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
            ->plannedFromTodayOnward()
            ->orderBy('tour_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (Tour $tour) => $this->decorateTour($tour));

        $nextTour = $upcomingTours->first();
        $laterUpcomingTours = $upcomingTours->slice(1)->values();

        $upcomingTourCount = (int) $upcomingTours->count();
        $upcomingParticipantCount = (int) $upcomingTours->sum('booked_people_count');

        $coGuideTours = $this->tourCoGuideService
            ->dashboardToursForCoGuide((int) $user->id)
            ->map(fn (Tour $tour) => $this->decorateTour($tour));

        $todayOpeningCheck = $this->openingChecks->forDate(now());
        $todayOpeningCheckCompleted = $todayOpeningCheck?->isCompleted() ?? false;
        $openingCheckTablesReady = $this->openingChecks->tablesExist();

        return view('guide.dashboard', compact(
            'ongoingTour',
            'upcomingTours',
            'laterUpcomingTours',
            'nextTour',
            'upcomingTourCount',
            'upcomingParticipantCount',
            'coGuideTours',
            'todayOpeningCheck',
            'todayOpeningCheckCompleted',
            'openingCheckTablesReady',
        ));
    }

    public function showTour(Tour $tour)
    {
        $this->ensureGuideOwnsTour($tour);

        $tourPhotosEnabled = Schema::hasTable('tour_photos');

        $relations = [
            'guide',
            'tourType',
            'bookings' => fn ($query) => $query->with('languages')->orderBy('created_at')->orderBy('id'),
        ];

        if ($tourPhotosEnabled) {
            $relations[] = 'photos.uploadedBy';
        }

        $tour->load($relations);

        if (! $tourPhotosEnabled) {
            $tour->setRelation('photos', collect());
        }

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
            'occupancyPercent',
            'tourPhotosEnabled',
        ));
    }

    public function startTour(Request $request, Tour $tour)
    {
        $this->ensureGuideOwnsTour($tour);

        if ($tour->status === 'completed') {
            return $this->guideTourActionErrorResponse(
                $request,
                'tour',
                'Det går inte att starta en redan avslutad tur.'
            );
        }

        if ($tour->status === 'started') {
            return $this->guideTourActionResponse($request, $tour->fresh(), 'Turen är redan startad.');
        }

        $data = $request->validate([
            'actual_on_site_count' => ['nullable', 'integer', 'min:1'],
        ]);

        $bookedTotal = $this->headcount->bookedTotal($tour);
        $actualTotal = isset($data['actual_on_site_count'])
            ? (int) $data['actual_on_site_count']
            : $bookedTotal;

        if ($actualTotal !== $bookedTotal) {
            $this->headcount->syncTourToActualCount(
                $tour,
                $actualTotal,
                (int) auth()->id(),
                "Guide justerade antal vid start: {$bookedTotal} → {$actualTotal}"
            );
        }

        $old = $tour->toArray();

        $tour->update([
            'status' => 'started',
            'started_at' => $tour->started_at ?: now(),
            'baseline_end_time' => $this->autoComplete->captureBaselineEndTime($tour->end_time),
            'updated_by' => auth()->id(),
        ]);

        $this->headcount->recordStartSnapshot($tour->fresh(), $bookedTotal, $actualTotal, (int) auth()->id());

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

        return $this->guideTourActionResponse($request, $tour->fresh(), 'Tur startad.');
    }

    public function adjustTourHeadcount(Request $request, Tour $tour)
    {
        $this->ensureGuideOwnsTour($tour);

        if ($tour->status !== 'started') {
            return $this->guideTourActionResponse(
                $request,
                $tour->fresh(),
                'Antalet var redan synkat.'
            );
        }

        $data = $request->validate([
            'actual_on_site_count' => ['required', 'integer', 'min:1'],
        ]);

        $currentTotal = $this->headcount->bookedTotal($tour);
        $targetTotal = (int) $data['actual_on_site_count'];

        if ($targetTotal !== $currentTotal) {
            $this->headcount->syncTourToActualCount(
                $tour,
                $targetTotal,
                (int) auth()->id(),
                "Guide justerade antal under tur: {$currentTotal} → {$targetTotal}"
            );

            $tour->update([
                'actual_total_at_start' => $targetTotal,
                'headcount_adjusted_at' => now(),
                'headcount_adjusted_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }

        return $this->guideTourActionResponse($request, $tour->fresh(), 'Antal uppdaterat.');
    }

    public function completeTour(Request $request, Tour $tour)
    {
        $this->ensureGuideOwnsTour($tour);

        if ($tour->status === 'completed') {
            return $this->guideTourActionResponse(
                $request,
                $tour->fresh(),
                'Turen är redan avslutad.',
                route('guide.dashboard')
            );
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

        return $this->guideTourActionResponse(
            $request,
            $tour->fresh(),
            'Tur avslutad.',
            route('guide.dashboard')
        );
    }

    public function updateBookingParticipants(Booking $booking, Request $request)
    {
        $tour = $booking->tour;
        $this->ensureGuideOwnsTour($tour);

        if ($tour->status === 'completed') {
            return $this->guideBookingUpdateResponse(
                $request,
                $tour,
                'Bokningen var redan synkad.'
            );
        }

        $this->normalizeParticipantRequest($request);

        $data = $request->validate([
            'men_count' => ['required', 'integer', 'min:0'],
            'women_count' => ['required', 'integer', 'min:0'],
            'youth_count' => ['required', 'integer', 'min:0'],
            'child_count' => ['required', 'integer', 'min:0'],
            'unspecified_count' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:preliminary,confirmed,cancelled,completed'],
        ]);

        $counts = $this->participants->normalize($data);
        $data = array_merge($data, $counts);

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

        return $this->guideBookingUpdateResponse(
            $request,
            $tour,
            'Bokningen uppdaterades.'
        );
    }

    protected function decorateTour(Tour $tour): Tour
    {
        $activeBookings = collect($tour->bookings ?? [])
            ->whereNotIn('status', ['cancelled'])
            ->where('is_waitlist', false);

        $summary = $this->participants->summarizeBookings($activeBookings);

        $tour->booked_people_count = $summary['total'];
        $tour->booking_groups_count = (int) $activeBookings->count();
        $tour->unspecified_people_count = $summary['unspecified'];
        $tour->category_summary = $this->participants->formatCategorySummary(
            $summary['men'],
            $summary['women'],
            $summary['youth'],
            $summary['children'],
            $summary['unspecified']
        );
        $tour->occupancy_percent = (int) (
            ($tour->max_participants ?? 0) > 0
                ? round(($tour->booked_people_count / $tour->max_participants) * 100)
                : 0
        );
        $tour->is_due_to_start = $tour->isDueToStart();
        $tour->language_codes = $activeBookings
            ->flatMap(fn (Booking $booking) => collect($booking->languages ?? [])->pluck('code'))
            ->filter()
            ->map(fn ($code) => strtoupper((string) $code))
            ->unique()
            ->values()
            ->all();

        return $tour;
    }

    protected function calculateTotal(array $data): int
    {
        return (int) ($data['men_count'] ?? 0)
            + (int) ($data['women_count'] ?? 0)
            + (int) ($data['youth_count'] ?? 0)
            + (int) ($data['child_count'] ?? 0);
    }

    protected function normalizeParticipantRequest(Request $request): void
    {
        $normalized = [];

        foreach (['men_count', 'women_count', 'youth_count', 'child_count'] as $field) {
            $value = $request->input($field);

            if ($value === '' || $value === null) {
                $normalized[$field] = 0;
            }
        }

        $unspecified = $request->input('unspecified_count');

        if ($unspecified === '' || $unspecified === null) {
            $normalized['unspecified_count'] = null;
        }

        if ($normalized !== []) {
            $request->merge($normalized);
        }
    }

    protected function guideBookingUpdateResponse(
        Request $request,
        Tour $tour,
        string $message
    ): JsonResponse|RedirectResponse {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect_url' => route('guide.tours.show', $tour),
            ]);
        }

        return redirect()
            ->route('guide.tours.show', $tour)
            ->with('success', $message);
    }

    protected function guideBookingUpdateErrorResponse(
        Request $request,
        string $field,
        string $message
    ): JsonResponse|RedirectResponse {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => [
                    $field => [$message],
                ],
            ], 422);
        }

        return back()->withErrors([
            $field => $message,
        ]);
    }

    protected function ensureGuideOwnsTour(Tour $tour): void
    {
        if ((int) $tour->guide_id !== (int) auth()->id()) {
            abort(403);
        }
    }

    protected function guideTourActionResponse(
        Request $request,
        Tour $tour,
        string $message,
        ?string $redirectUrl = null
    ): JsonResponse|RedirectResponse {
        if ($request->ajax() || $request->expectsJson()) {
            $payload = [
                'status' => $tour->status,
                'started_at' => $tour->started_at?->format('H:i'),
                'ended_at' => $tour->ended_at?->format('H:i'),
                'message' => $message,
            ];

            if ($redirectUrl !== null) {
                $payload['redirect_url'] = $redirectUrl;
            }

            return response()->json($payload);
        }

        if ($redirectUrl !== null) {
            return redirect()->to($redirectUrl)->with('success', $message);
        }

        return back()->with('success', $message);
    }

    protected function guideTourActionErrorResponse(Request $request, string $field, string $message): JsonResponse|RedirectResponse
    {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => [
                    $field => [$message],
                ],
            ], 422);
        }

        return back()->withErrors([
            $field => $message,
        ]);
    }
}
