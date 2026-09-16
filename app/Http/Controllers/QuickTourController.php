<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Language;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Services\BookingParticipantService;
use App\Services\GuideQuickTourGuardService;
use App\Services\LogService;
use App\Services\TourAutoCompleteService;
use App\Services\TourDurationSettingsService;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class QuickTourController extends Controller
{
    public function __construct(
        private BookingParticipantService $participants,
        private TourDurationSettingsService $durationSettings,
        private TourAutoCompleteService $autoComplete,
        private GuideQuickTourGuardService $quickTourGuard,
    ) {}

    public function create()
    {
        $guides = User::query()
            ->whereHas('roles', function ($query) {
                $query->where('slug', Roles::GUIDE);
            })
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
            $quickTourAssessment = $this->quickTourGuard->assess((int) auth()->id());

            return view('guide.quick-tours.create', compact(
                'languages',
                'defaultLanguageIds',
                'quickTourAssessment',
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
            'participant_count' => ['nullable', 'integer', 'min:1'],
            'men_count' => ['nullable', 'integer', 'min:0'],
            'women_count' => ['nullable', 'integer', 'min:0'],
            'youth_count' => ['nullable', 'integer', 'min:0'],
            'child_count' => ['nullable', 'integer', 'min:0'],
            'unspecified_count' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'guide_id' => ['nullable', 'exists:users,id'],
            'language_ids' => ['nullable', 'array'],
            'language_ids.*' => ['integer', 'exists:languages,id'],
        ], [
            'guide_id.exists' => 'Vald guide finns inte.',
            'language_ids.*.exists' => 'Ett valt språk finns inte.',
        ]);

        $counts = $this->participants->normalize($data);
        $totalCount = $counts['total_count'];

        $guideId = $this->resolveGuideId($data);

        if ($guideId !== null) {
            $blockingTour = $this->quickTourGuard->blockingTour($guideId);

            if ($blockingTour !== null) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'quick_tour' => $this->quickTourGuard->blockedMessage($blockingTour, now(), $guideId),
                    ]);
            }
        }

        $now = now();

        $tourType = TourType::query()
            ->where('is_default', true)
            ->first();

        if (! $tourType) {
            $tourType = TourType::query()->orderBy('id')->first();
        }

        $endTime = $this->durationSettings->endTimeFromStart($now, $tourType?->id);

        $tourTitle = 'Snabbtur '.$now->format('Y-m-d H:i');
        $bookingName = 'Snabbtur '.$now->format('Y-m-d H:i');

        $languageIds = collect($data['language_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($languageIds)) {
            $languageIds = Language::query()
                ->where('code', 'sv')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        if (empty($languageIds)) {
            $languageIds = Language::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(1)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        $tour = Tour::create([
            'title' => $tourTitle,
            'tour_date' => $now->toDateString(),
            'start_time' => $now->format('H:i:s'),
            'end_time' => $endTime,
            'baseline_end_time' => $this->autoComplete->captureBaselineEndTime($endTime),
            'status' => 'started',
            'started_at' => $now,
            'tour_type_id' => $tourType?->id,
            'guide_id' => $guideId,
            'max_participants' => $this->durationSettings->resolveCapacityForQuickTour($totalCount),
            'booked_total_at_start' => $totalCount,
            'actual_total_at_start' => $totalCount,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $booking = Booking::create([
            'tour_id' => $tour->id,
            'booking_name' => $bookingName,
            'contact_name' => $bookingName,
            'men_count' => $counts['men_count'],
            'women_count' => $counts['women_count'],
            'youth_count' => $counts['youth_count'],
            'child_count' => $counts['child_count'],
            'unspecified_count' => $counts['unspecified_count'],
            'total_count' => $counts['total_count'],
            'notes' => $data['notes'] ?? null,
            'status' => 'confirmed',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        if (method_exists($booking, 'languages')) {
            $booking->languages()->sync($languageIds);
        }

        if (class_exists(LogService::class)) {
            LogService::log(
                'tour',
                $tour->id,
                'created',
                null,
                $tour->toArray(),
                'Skapade snabbtur'
            );

            LogService::log(
                'booking',
                $booking->id,
                'created',
                null,
                $booking->fresh()->toArray(),
                'Skapade bokning via snabbtur'
            );
        }

        if (session('active_role') === Roles::GUIDE) {
            return redirect()
                ->route('guide.dashboard')
                ->with('success', 'Snabbtur startad.')
                ->with('warm_guide_tour_id', $tour->id);
        }

        $prefix = match (session('active_role')) {
            Roles::HOST => 'host',
            Roles::ADMIN => 'admin',
            default => null,
        };

        if ($prefix && Route::has($prefix.'.dashboard')) {
            return redirect()
                ->route($prefix.'.dashboard')
                ->with('success', 'Snabbtur startad.');
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Snabbtur startad.');
    }

    protected function resolveGuideId(array $data): ?int
    {
        if (session('active_role') === Roles::GUIDE) {
            return auth()->id();
        }

        return ! empty($data['guide_id']) ? (int) $data['guide_id'] : null;
    }
}
