<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\GuideShift;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Services\DailyGuideOrderService;
use App\Services\GuideLanguageMatchService;
use App\Services\LogService;
use App\Services\TourAutoCompleteService;
use App\Services\TourCoGuideService;
use App\Services\TourDeletionService;
use App\Services\TourDurationSettingsService;
use App\Services\TourEarlyStartService;
use App\Services\TourWaitTimeService;
use App\Support\ActiveRole;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class TourController extends Controller
{
    public function __construct(
        private DailyGuideOrderService $dailyGuideOrderService,
        private GuideLanguageMatchService $guideLanguageMatchService,
        private TourCoGuideService $tourCoGuideService,
        private TourWaitTimeService $tourWaitTimeService,
    ) {}

    public function index(Request $request)
    {
        $scope = $request->get('scope', 'upcoming');
        $waitWarningMinutes = $this->tourWaitTimeService->warningMinutes();

        $query = Tour::with(app(TourCoGuideService::class)->tourDisplayRelations());

        if ($request->filled('q')) {
            $search = trim((string) $request->q);

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('guide', function ($guideQuery) use ($search) {
                        $guideQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('coGuides', function ($coGuideQuery) use ($search) {
                        $coGuideQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('tourType', function ($typeQuery) use ($search) {
                        $typeQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('tour_date', $request->date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($scope === 'archive') {
            $query->where(function ($q) {
                $q->whereDate('tour_date', '<', now()->toDateString())
                    ->orWhereIn('status', ['completed', 'cancelled']);
            })
                ->orderByDesc('tour_date')
                ->orderByDesc('start_time');
        } else {
            $query->where(function ($q) {
                $q->whereDate('tour_date', '>', now()->toDateString())
                    ->orWhere(function ($qq) {
                        $qq->whereDate('tour_date', now()->toDateString())
                            ->whereNotIn('status', ['completed', 'cancelled']);
                    });
            })
                ->orderBy('tour_date')
                ->orderBy('start_time');
        }

        $tours = $query->paginate(20)->withQueryString();

        $tours->getCollection()->transform(function (Tour $tour) {
            return $this->decorateTourBookingCounts($tour);
        });

        if ($scope === 'archive') {
            $this->tourWaitTimeService->attachGroupedByDate(
                $tours->getCollection(),
                $waitWarningMinutes,
            );
        }

        return view('admin.tours.index', compact('tours', 'scope', 'waitWarningMinutes'));
    }

    public function create()
    {
        $tourTypes = TourType::activeOrdered();

        $defaultTourTypeId = TourType::where('is_default', true)->value('id');

        $tour = new Tour;
        $tour->tour_date = now()->toDateString();
        $tour->max_participants = (int) setting('default_tour_capacity', 25);
        $tour->status = 'planned';
        $tour->tour_type_id = $defaultTourTypeId;

        $guides = $this->guideUsersForDate($tour->tour_date);
        $traineeCandidates = $this->tourCoGuideService->traineeCandidatesForDate($tour->tour_date);

        return view('admin.tours.create', [
            'tour' => $tour,
            'guides' => $guides,
            'traineeCandidates' => $traineeCandidates,
            'tourTypes' => $tourTypes,
            'defaultTourTypeId' => $defaultTourTypeId,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $data['end_time'] = $this->resolveEndTime(
            $data['start_time'] ?? null,
            $data['end_time'] ?? null,
            isset($data['tour_type_id']) ? (int) $data['tour_type_id'] : null,
            $data['tour_date'] ?? null,
        );

        if (blank($data['title'] ?? null) && (bool) setting('auto_generate_tour_title', 1)) {
            $data['title'] = $this->generateTourTitle($data);
        }

        if (($data['status'] ?? null) === 'started') {
            $data['started_at'] = now();
            $data['baseline_end_time'] = app(TourAutoCompleteService::class)->captureBaselineEndTime($data['end_time'] ?? null);
        }

        if (($data['status'] ?? null) === 'completed') {
            $data['started_at'] = now();
            $data['ended_at'] = now();
        }

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $tour = Tour::create($data);
        $tour->default_includes_meal = $this->resolveDefaultIncludesMeal($request);
        $tour->exclude_from_booking_sequence = $request->boolean('exclude_from_booking_sequence');
        $tour->exclude_from_schedule_statistics = $request->boolean('exclude_from_schedule_statistics');
        $tour->save();
        $tour->refresh();

        $this->syncCoGuides($tour, $request);
        $this->syncShift($tour);

        LogService::log(
            'tour',
            $tour->id,
            'created',
            null,
            $tour->toArray(),
            'Skapade tur'
        );

        return redirect()
            ->route($this->routePrefix().'.tours.index')
            ->with('success', 'Tur skapad.');
    }

    public function show(Tour $tour)
    {
        $tour->load([
            'guide.guideLanguages',
            'tourType',
            'bookings' => fn ($query) => $query->with('languages')->orderBy('created_at')->orderBy('id'),
            'photos.uploadedBy',
        ]);

        if (Schema::hasTable('tour_guide')) {
            $tour->load('coGuides');
        } else {
            $tour->setRelation('coGuides', collect());
        }

        $guideLanguageMismatch = $tour->guide
            ? $this->guideLanguageMatchService->assessGuideForTour($tour->guide, $tour)
            : null;

        $bookingCount = $tour->bookings()
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $bookedCount = $tour->bookings()
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_count');

        $availableSpots = max(0, (int) $tour->max_participants - (int) $bookedCount);

        $occupancyPercent = $tour->max_participants > 0
            ? round(($bookedCount / $tour->max_participants) * 100)
            : 0;

        $lifecycleLogs = ActivityLog::with('user')
            ->where('entity_type', 'tour')
            ->where('entity_id', $tour->id)
            ->whereIn('action', ['started', 'completed'])
            ->orderByDesc('created_at')
            ->get();

        $startedLog = $lifecycleLogs->firstWhere('action', 'started');
        $completedLog = $lifecycleLogs->firstWhere('action', 'completed');

        $createdByUser = $tour->created_by
            ? User::query()->find($tour->created_by)
            : null;
        $updatedByUser = $tour->updated_by
            ? User::query()->find($tour->updated_by)
            : null;

        return view('admin.tours.show', compact(
            'tour',
            'bookingCount',
            'bookedCount',
            'availableSpots',
            'occupancyPercent',
            'startedLog',
            'completedLog',
            'guideLanguageMismatch',
            'createdByUser',
            'updatedByUser',
        ));
    }

    public function edit(Tour $tour)
    {
        $tourTypes = TourType::activeOrdered();

        $defaultTourTypeId = TourType::where('is_default', true)->value('id');

        $guides = $this->guideUsersForDate($tour->tour_date);
        $traineeCandidates = $this->tourCoGuideService->traineeCandidatesForDate($tour->tour_date);

        if (Schema::hasTable('tour_guide')) {
            $tour->load('coGuides');
        } else {
            $tour->setRelation('coGuides', collect());
        }

        $tour->refresh();

        return view('admin.tours.edit', [
            'tour' => $tour,
            'guides' => $guides,
            'traineeCandidates' => $traineeCandidates,
            'tourTypes' => $tourTypes,
            'defaultTourTypeId' => $defaultTourTypeId,
        ]);
    }

    public function update(Request $request, Tour $tour)
    {
        $old = $tour->toArray();
        $oldStatus = $tour->status;
        $oldGuideId = $tour->guide_id;
        $oldTourDate = $tour->tour_date?->toDateString();

        $data = $this->validated($request);

        $data['end_time'] = $this->resolveEndTime(
            $data['start_time'] ?? null,
            $data['end_time'] ?? null,
            isset($data['tour_type_id']) ? (int) $data['tour_type_id'] : null,
            $data['tour_date'] ?? $tour->tour_date?->toDateString(),
        );

        if (blank($data['title'] ?? null) && (bool) setting('auto_generate_tour_title', 1)) {
            $data['title'] = $this->generateTourTitle($data);
        }

        if (($data['status'] ?? null) === 'started' && empty($tour->started_at)) {
            $data['started_at'] = now();
            $data['baseline_end_time'] = app(TourAutoCompleteService::class)->captureBaselineEndTime($data['end_time'] ?? $tour->end_time);
        }

        if (($data['status'] ?? null) === 'completed' && empty($tour->ended_at)) {
            $data['ended_at'] = now();

            if (empty($tour->started_at)) {
                $data['started_at'] = now();
            }
        }

        if ($oldStatus === 'started' && ($data['status'] ?? null) !== 'started' && ($data['status'] ?? null) !== 'completed') {
            $data['started_at'] = null;
        }

        if ($oldStatus === 'completed' && ($data['status'] ?? null) !== 'completed') {
            $data['ended_at'] = null;
        }

        $data['updated_by'] = auth()->id();

        $tour->update($data);

        $includesMeal = $this->resolveDefaultIncludesMeal($request);
        $tour->default_includes_meal = $includesMeal;
        $tour->exclude_from_booking_sequence = $request->boolean('exclude_from_booking_sequence');
        $tour->exclude_from_schedule_statistics = $request->boolean('exclude_from_schedule_statistics');
        $tour->save();

        $this->syncCoGuides($tour, $request);
        $this->syncShift($tour);

        $tour->refresh();

        $guideChanged = (int) ($oldGuideId ?? 0) !== (int) ($tour->guide_id ?? 0);
        $rippleResult = ['updated' => 0, 'skipped' => 0];

        if (
            $guideChanged
            && $request->boolean('ripple_subsequent_guides')
            && $oldTourDate === $tour->tour_date?->toDateString()
        ) {
            $rippleResult = $this->dailyGuideOrderService->rippleGuidesAfterTour(
                $tour,
                $tour->guide_id ? (int) $tour->guide_id : null,
                auth()->id()
            );

            foreach ($rippleResult['tours'] as $updatedTour) {
                $this->syncShift($updatedTour);
            }
        }

        LogService::log(
            'tour',
            $tour->id,
            'updated',
            $old,
            $tour->toArray(),
            'Uppdaterade tur'
        );

        $mealLabel = $tour->default_includes_meal ? 'Med mat' : 'Ej mat';
        $successMessage = "Tur uppdaterad. Matstandard: {$mealLabel}.";

        if ($guideChanged && $request->boolean('ripple_subsequent_guides')) {
            if ($rippleResult['updated'] > 0) {
                $successMessage .= " {$rippleResult['updated']} efterföljande tur(er) fick ny guide enligt dagens ordning.";
            }

            if ($rippleResult['skipped'] > 0) {
                $successMessage .= " {$rippleResult['skipped']} pågående/avslutade tur(er) lämnades oförändrade.";
            }
        }

        $redirect = redirect()
            ->route($this->routePrefix().'.dashboard')
            ->with('success', $successMessage);

        if ($tour->guide) {
            $mismatch = $this->guideLanguageMatchService->assessGuideForTour($tour->guide, $tour);

            if ($mismatch['has_language_mismatch']) {
                $redirect->with('warning', $mismatch['message']);
            }
        }

        return $redirect;
    }

    public function cancel(Tour $tour)
    {
        $old = $tour->toArray();

        $tour->update([
            'status' => 'cancelled',
            'updated_by' => auth()->id(),
        ]);

        LogService::log(
            'tour',
            $tour->id,
            'cancelled',
            $old,
            $tour->fresh()->toArray(),
            'Ställde in tur från dashboard'
        );

        return back()->with('success', 'Turen har ställts in.');
    }

    public function destroy(Tour $tour, TourDeletionService $tourDeletionService)
    {
        abort_unless(session('active_role') === Roles::ADMIN, 403);

        $old = $tour->toArray();

        $result = $tourDeletionService->delete($tour);

        LogService::log(
            'tour',
            $tour->id,
            'deleted',
            $old,
            [
                'bookings_deleted' => $result['bookings_deleted'],
                'photos_deleted' => $result['photos_deleted'],
            ],
            'Tog bort tur med tillhörande bokningar'
        );

        $message = $result['bookings_deleted'] > 0
            ? "Tur och {$result['bookings_deleted']} bokning(ar) borttagna."
            : 'Tur borttagen.';

        return redirect()
            ->route('admin.tours.index', ['scope' => request('scope', 'upcoming')])
            ->with('success', $message);
    }

    public function start(Request $request, Tour $tour, TourEarlyStartService $earlyStartService)
    {
        if ($tour->status === 'completed') {
            return back()->withErrors([
                'tour' => 'En avslutad tur kan inte startas igen.',
            ]);
        }

        if ($earlyStartService->requiresConfirmation($tour) && ! $request->boolean('confirm_early_start')) {
            return back()->withErrors([
                'tour' => $earlyStartService->validationErrorMessage($tour),
            ]);
        }

        $tour->update([
            'status' => 'started',
            'started_at' => now(),
            'baseline_end_time' => app(TourAutoCompleteService::class)->captureBaselineEndTime($tour->end_time),
            'updated_by' => auth()->id(),
        ]);

        LogService::log(
            'tour',
            $tour->id,
            'started',
            null,
            [
                'status' => 'started',
                'started_at' => now(),
            ],
            'Startade tur'.($request->boolean('confirm_early_start') ? ' (tidig start, bekräftad)' : '')
        );

        return back()->with('success', 'Tur startad.');
    }

    public function complete(Tour $tour)
    {
        if ($tour->status === 'completed') {
            return back()->withErrors([
                'tour' => 'Turen är redan avslutad.',
            ]);
        }

        $tour->update([
            'status' => 'completed',
            'ended_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        LogService::log(
            'tour',
            $tour->id,
            'completed',
            null,
            [
                'status' => 'completed',
                'ended_at' => now(),
            ],
            'Avslutade tur'
        );

        return back()->with('success', 'Tur avslutad.');
    }

    public function closeForBookings(Request $request, Tour $tour)
    {
        if (! in_array($tour->status, ['planned', 'started'], true)) {
            return $this->closeBookingsRedirect($request)->withErrors([
                'tour' => 'Endast planerade eller pågående turer kan stängas för bokning.',
            ]);
        }

        if ($tour->closed_for_bookings) {
            return $this->closeBookingsRedirect($request)->with('success', 'Turen är redan stängd för bokning.');
        }

        $tour->update([
            'closed_for_bookings' => true,
            'updated_by' => auth()->id(),
        ]);

        LogService::log(
            'tour',
            $tour->id,
            'bookings_closed',
            ['closed_for_bookings' => false],
            ['closed_for_bookings' => true],
            'Stängde tur för fler bokningar i bokningssekvensen'
        );

        return $this->closeBookingsRedirect($request)->with('success', 'Turen är stängd för fler bokningar.');
    }

    public function reopenForBookings(Request $request, Tour $tour)
    {
        if (! $tour->closed_for_bookings) {
            return $this->closeBookingsRedirect($request)->with('success', 'Turen är redan öppen för bokning.');
        }

        if (! in_array($tour->status, ['planned', 'started'], true)) {
            return $this->closeBookingsRedirect($request)->withErrors([
                'tour' => 'Endast planerade eller pågående turer kan öppnas för bokning.',
            ]);
        }

        $tour->update([
            'closed_for_bookings' => false,
            'updated_by' => auth()->id(),
        ]);

        LogService::log(
            'tour',
            $tour->id,
            'bookings_reopened',
            ['closed_for_bookings' => true],
            ['closed_for_bookings' => false],
            'Öppnade tur för bokning i bokningssekvensen'
        );

        return $this->closeBookingsRedirect($request)->with('success', 'Turen är öppen för bokning igen.');
    }

    private function closeBookingsRedirect(Request $request): RedirectResponse
    {
        if ($request->input('return_to') === 'quick-booking' && Route::has(ActiveRole::routePrefix().'.bookings.quick-create')) {
            return redirect()->route(ActiveRole::routePrefix().'.bookings.quick-create');
        }

        return back();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'tour_type_id' => ['nullable', 'exists:tour_types,id'],
            'description' => ['nullable', 'string'],
            'tour_date' => ['required', 'date'],
            'start_time' => ['required'],
            'end_time' => ['nullable'],
            'max_participants' => ['required', 'integer', 'min:1'],
            'guide_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', 'in:planned,started,completed,cancelled'],
        ]);
    }

    private function resolveDefaultIncludesMeal(Request $request): bool
    {
        return (string) $request->input('default_includes_meal', '0') === '1';
    }

    private function decorateTourBookingCounts(Tour $tour): Tour
    {
        $activeBookings = collect($tour->bookings ?? [])
            ->whereNotIn('status', ['cancelled'])
            ->where('is_waitlist', false);

        $tour->booked_people_count = (int) $activeBookings->sum('total_count');
        $tour->booking_groups_count = (int) $activeBookings->count();

        return $tour;
    }

    private function generateTourTitle(array $data): string
    {
        $typeName = 'Tur';

        if (! empty($data['tour_type_id'])) {
            $type = TourType::find($data['tour_type_id']);

            if ($type) {
                $typeName = $type->name;
            }
        }

        $date = ! empty($data['tour_date'])
            ? date('Y-m-d', strtotime($data['tour_date']))
            : now()->toDateString();

        $time = $data['start_time'] ?? '00:00';

        return trim($typeName.' '.$date.' '.$time);
    }

    private function syncShift(Tour $tour): void
    {
        GuideShift::where('tour_id', $tour->id)
            ->when($tour->guide_id, function ($query) use ($tour) {
                $query->where('guide_id', '!=', $tour->guide_id);
            })
            ->delete();

        if (! $tour->guide_id) {
            return;
        }

        GuideShift::updateOrCreate(
            [
                'tour_id' => $tour->id,
                'guide_id' => $tour->guide_id,
            ],
            [
                'shift_type' => 'tour',
                'title' => $tour->title,
                'shift_date' => $tour->tour_date,
                'start_time' => $tour->start_time,
                'end_time' => $tour->end_time ?? $tour->start_time,
                'notes' => $tour->description,
                'created_by' => $tour->created_by,
                'updated_by' => auth()->id(),
            ]
        );
    }

    private function resolveEndTime(?string $startTime, ?string $endTime = null, ?int $tourTypeId = null, ?string $tourDate = null): ?string
    {
        if (! $startTime) {
            return $endTime;
        }

        if (! empty($endTime)) {
            return $endTime;
        }

        return app(TourDurationSettingsService::class)
            ->endTimeFromStartTime($startTime, $tourTypeId);
    }

    private function syncCoGuides(Tour $tour, Request $request): void
    {
        if (! Schema::hasTable('tour_guide')) {
            return;
        }

        $this->tourCoGuideService->validateCoGuideSelection(
            $request,
            $tour->guide_id ? (int) $tour->guide_id : null
        );

        $this->tourCoGuideService->sync(
            $tour,
            $request->input('assistant_guide_ids', []),
            $request->input('trainee_guide_ids', []),
            $request->input('co_guide_notes', [])
        );
    }

    private function guideUsersForDate(?string $date)
    {
        return User::query()
            ->whereHas('roles', function ($query) {
                $query->where('slug', Roles::GUIDE);
            })
            ->with(['workShifts' => function ($query) use ($date) {
                if ($date) {
                    $query->whereDate('shift_date', $date);
                }

                $query->where('shift_role', Roles::GUIDE)
                    ->whereNotIn('status', ['cancelled'])
                    ->orderBy('start_time');
            }])
            ->orderBy('name')
            ->get();
    }

    private function routePrefix(): string
    {
        return ActiveRole::routePrefix();
    }
}
