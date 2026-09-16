<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Country;
use App\Models\Language;
use App\Models\Tour;
use App\Models\User;
use App\Services\BookingInvoiceNotificationService;
use App\Services\BookingParticipantService;
use App\Services\CountryProposalService;
use App\Services\GuideLanguageMatchService;
use App\Services\LogService;
use App\Services\NotificationService;
use App\Services\TourDayLoadService;
use App\Support\ActiveRole;
use App\Support\CountryCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(
        private BookingParticipantService $participants,
        private GuideLanguageMatchService $guideLanguageMatchService,
        private BookingInvoiceNotificationService $bookingInvoiceNotifications,
        private CountryProposalService $countryProposalService,
        private TourDayLoadService $tourDayLoad,
    ) {}

    public function index(Request $request)
    {
        $scope = $request->get('scope', 'active');

        $query = Booking::with(['tour', 'tour.guide', 'tour.tourType', 'languages']);

        if ($scope === 'archive') {
            $query->where(function ($q) {
                $q->whereHas('tour', function ($tourQuery) {
                    $tourQuery->whereDate('tour_date', '<', now()->toDateString());
                })->orWhereIn('status', ['cancelled', 'completed']);
            });
        } else {
            $query->where(function ($q) {
                $q->whereHas('tour', function ($tourQuery) {
                    $tourQuery->whereDate('tour_date', '>=', now()->toDateString());
                })->whereNotIn('status', ['cancelled', 'completed']);
            });
        }

        if ($request->filled('q')) {
            $this->applyBookingSearch($query, trim((string) $request->q));
        }

        if ($request->filled('date')) {
            $query->whereHas('tour', function ($builder) use ($request) {
                $builder->whereDate('tour_date', $request->date);
            });
        }

        if ($request->filled('arrival_status')) {
            $query->where('arrival_status', $request->arrival_status);
        }

        if ($request->boolean('to_be_invoiced_only')) {
            $query->where('to_be_invoiced', true);
        }

        $bookings = $query->latest()->paginate(20)->withQueryString();

        return view('admin.bookings.index', compact('bookings', 'scope'));
    }

    public function create(Request $request)
    {
        $selectedTourId = $request->integer('tour_id') ?: null;
        $tourGroups = $this->toursGroupedForBookingForm($selectedTourId);

        $languages = Language::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $defaultIncludesMeal = false;

        if ($selectedTourId) {
            $defaultIncludesMeal = (bool) Tour::query()
                ->whereKey($selectedTourId)
                ->value('default_includes_meal');
        }

        return view('admin.bookings.create', [
            'booking' => new Booking,
            'tours' => $tourGroups['upcoming']->merge($tourGroups['historical']),
            'upcomingTours' => $tourGroups['upcoming'],
            'historicalTours' => $tourGroups['historical'],
            'languages' => $languages,
            'selectedTourId' => $selectedTourId,
            'defaultIncludesMeal' => $defaultIncludesMeal,
            'defaultLanguageIds' => Language::defaultIds(),
            'tourSearchUrl' => route(ActiveRole::routeName('bookings.tours.search')),
            ...$this->countryFormOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $tour = Tour::findOrFail($data['tour_id']);

        $this->ensureTourCanBeBooked($tour);

        if (blank($data['booking_name'] ?? null)) {
            $data['booking_name'] = $this->generateBookingName($tour);
        }

        $data = $this->prepareBookingData($data, $request);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        $data['duplicate_warning'] = $this->hasDuplicateWarning($data);
        $data['is_waitlist'] = false;

        $booking = null;

        $languageIds = $this->resolveLanguageIds($request);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                if (blank($data['booking_name'] ?? null)) {
                    $data['booking_name'] = $this->generateBookingName($tour);
                }

                $booking = Booking::create($data);
                $booking->languages()->sync($languageIds);

                break;
            } catch (QueryException $e) {
                $isDuplicate =
                    str_contains($e->getMessage(), 'Duplicate entry') ||
                    str_contains($e->getMessage(), 'booking_name');

                if (! $isDuplicate) {
                    throw $e;
                }

                $data['booking_name'] = $this->generateBookingName($tour);
            }
        }

        if (! $booking) {
            throw ValidationException::withMessages([
                'booking_name' => 'Kunde inte skapa unikt boknings-ID. Försök igen.',
            ]);
        }

        LogService::log(
            'booking',
            $booking->id,
            'created',
            null,
            $booking->fresh(['languages'])->toArray(),
            'Skapade bokning'
        );

        $this->sendBookingNotificationAfterStore($booking);
        $invoiceWarning = $this->bookingInvoiceNotifications->notifyAfterStore($booking);

        if ($request->boolean('from_tour')) {
            return $this->redirectWithBookingGuideLanguageWarning(
                redirect()
                    ->route($this->routePrefix().'.tours.show', $tour)
                    ->with('success', 'Bokning skapad.'),
                $tour,
                $languageIds,
                $invoiceWarning
            );
        }

        return $this->redirectWithBookingGuideLanguageWarning(
            $this->redirectAfterBookingChange($tour, 'Bokning skapad.'),
            $tour,
            $languageIds,
            $invoiceWarning
        );
    }

    public function edit(Booking $booking, Request $request)
    {
        $this->ensureBookingCanBeEdited($booking);

        $currentTourId = $booking->tour_id;
        $tourGroups = $this->toursGroupedForBookingForm($currentTourId);

        $languages = Language::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if (Schema::hasTable('notification_logs')) {
            $booking->load(['notificationLogs' => fn ($q) => $q->latest()]);
        } else {
            $booking->setRelation('notificationLogs', collect());
        }

        $booking->loadMissing('tour');

        $createdByUser = $booking->created_by
            ? User::query()->find($booking->created_by)
            : null;
        $updatedByUser = $booking->updated_by
            ? User::query()->find($booking->updated_by)
            : null;

        return view('admin.bookings.edit', [
            'booking' => $booking,
            'createdByUser' => $createdByUser,
            'updatedByUser' => $updatedByUser,
            'tours' => $tourGroups['upcoming']->merge($tourGroups['historical']),
            'upcomingTours' => $tourGroups['upcoming'],
            'historicalTours' => $tourGroups['historical'],
            'languages' => $languages,
            'fromTour' => $request->boolean('from_tour'),
            'isRetroactiveBooking' => $this->allowsRetroactiveBooking($booking->tour),
            'tourSearchUrl' => route(ActiveRole::routeName('bookings.tours.search')),
            ...$this->countryFormOptions(),
        ]);
    }

    public function update(Request $request, Booking $booking)
    {
        $this->ensureBookingCanBeEdited($booking);

        $old = $booking->toArray();
        $oldStatus = $booking->status;

        $data = $this->validated($request, $booking);
        $tour = Tour::findOrFail($data['tour_id']);

        $this->ensureTourCanBeBooked($tour, true);

        if (blank($data['booking_name'] ?? null)) {
            $data['booking_name'] = $booking->booking_name ?: $this->generateBookingName($tour);
        }

        $data = $this->prepareBookingData($data, $request);
        $data['updated_by'] = auth()->id();
        $data['duplicate_warning'] = $this->hasDuplicateWarning($data, $booking->id);
        $data['is_waitlist'] = false;

        $booking->update($data);
        $languageIds = $this->resolveLanguageIds($request);
        $booking->languages()->sync($languageIds);

        LogService::log(
            'booking',
            $booking->id,
            'updated',
            $old,
            $booking->fresh(['languages'])->toArray(),
            'Uppdaterade bokning'
        );

        $this->sendBookingNotificationAfterUpdate($booking, $oldStatus);

        $booking->loadMissing('tour');

        if ($request->boolean('from_tour') && $booking->tour) {
            return $this->redirectWithBookingGuideLanguageWarning(
                redirect()
                    ->route($this->routePrefix().'.tours.show', $booking->tour)
                    ->with('success', 'Bokning uppdaterad.'),
                $tour,
                $languageIds
            );
        }

        return $this->redirectWithBookingGuideLanguageWarning(
            $this->redirectAfterBookingChange($booking->tour, 'Bokning uppdaterad.'),
            $tour,
            $languageIds
        );
    }

    public function destroy(Request $request, Booking $booking)
    {
        $booking->loadMissing('tour');

        $tour = $booking->tour;
        $old = $booking->toArray();

        $booking->languages()->detach();
        $booking->delete();

        LogService::log(
            'booking',
            $booking->id,
            'deleted',
            $old,
            null,
            'Tog bort bokning'
        );

        if ($request->boolean('from_tour') && $tour) {
            return redirect()
                ->route($this->routePrefix().'.tours.show', $tour)
                ->with('success', 'Bokning borttagen.');
        }

        $scope = $request->get('scope');

        return redirect()
            ->route($this->routePrefix().'.bookings.index', array_filter([
                'scope' => $scope,
            ]))
            ->with('success', 'Bokning borttagen.');
    }

    public function move(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'new_tour_id' => ['required', 'exists:tours,id'],
        ]);

        $newTour = Tour::findOrFail($data['new_tour_id']);

        $this->ensureTourCanBeBooked($newTour, true);

        $old = $booking->toArray();

        $booking->update([
            'moved_from_tour_id' => $booking->tour_id,
            'tour_id' => $newTour->id,
            'updated_by' => auth()->id(),
            'is_waitlist' => false,
        ]);

        LogService::log(
            'booking',
            $booking->id,
            'moved',
            $old,
            $booking->fresh()->toArray(),
            'Flyttade bokning till annan tur'
        );

        $this->sendBookingUpdatedNotification($booking);

        $booking->load('languages');

        return $this->redirectWithBookingGuideLanguageWarning(
            back()->with('success', 'Bokningen flyttades.'),
            $newTour,
            $booking->languages->pluck('id')->map(fn ($id) => (int) $id)->all()
        );
    }

    public function markArrival(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'arrival_status' => ['required', 'in:booked,arrived,no_show,late_cancel'],
        ]);

        $payload = [
            'arrival_status' => $data['arrival_status'],
            'updated_by' => auth()->id(),
        ];

        if ($data['arrival_status'] === 'arrived') {
            $payload['checked_in_at'] = now();
        }

        $booking->update($payload);

        LogService::log(
            'booking',
            $booking->id,
            'arrival_status_updated',
            null,
            $booking->fresh()->toArray(),
            'Uppdaterade ankomststatus'
        );

        return back()->with('success', 'Ankomststatus uppdaterad.');
    }

    public function quickUpdateParticipants(Booking $booking, Request $request): RedirectResponse
    {
        $this->ensureBookingCanBeEdited($booking);

        $oldStatus = $booking->status;
        $returnToFollowUp = $request->input('return_to') === 'unspecified-follow-up';

        $data = $request->validate([
            'men_count' => ['required', 'integer', 'min:0'],
            'women_count' => ['required', 'integer', 'min:0'],
            'youth_count' => ['required', 'integer', 'min:0'],
            'child_count' => ['required', 'integer', 'min:0'],
            'unspecified_count' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:preliminary,confirmed,cancelled,completed'],
            'return_to' => ['nullable', 'string', 'in:unspecified-follow-up'],
            'follow_up_from' => ['nullable', 'date'],
            'follow_up_to' => ['nullable', 'date'],
            'follow_up_scope' => ['nullable', 'in:all,upcoming,completed'],
            'follow_up_page' => ['nullable', 'integer', 'min:1'],
            'follow_up_booking_id' => ['nullable', 'integer'],
        ]);

        $tour = $booking->tour;

        if ($returnToFollowUp) {
            $data['total_count'] = $booking->total_count;
        }

        $counts = $this->participants->normalize($data);
        $data = array_merge($data, $counts);

        $data['updated_by'] = auth()->id();

        $booking->update($data);

        LogService::log(
            'booking',
            $booking->id,
            'quick_updated',
            null,
            $booking->fresh()->toArray(),
            $returnToFollowUp
                ? 'Kompletterade fördelning m/k/u/b i uppföljning av ospecificerade'
                : 'Uppdaterade deltagare direkt i turvyn'
        );

        $this->sendBookingNotificationAfterUpdate($booking, $oldStatus);

        return $this->redirectAfterQuickParticipantUpdate($request, $tour, $returnToFollowUp);
    }

    public function searchTours(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        if (mb_strlen($search) < 2) {
            return response()->json(['tours' => []]);
        }

        $like = '%'.addcslashes($search, '%_\\').'%';

        $tours = $this->bookingFormTourQuery()
            ->where('status', '!=', 'cancelled')
            ->where(function (Builder $query) use ($search, $like) {
                $query->where('title', 'like', $like)
                    ->orWhere('tour_date', 'like', addcslashes($search, '%_\\').'%');
            })
            ->orderByDesc('tour_date')
            ->orderBy('start_time')
            ->limit(30)
            ->get();

        return response()->json([
            'tours' => $tours->map(fn (Tour $tour) => [
                'id' => $tour->id,
                'group' => $this->isUpcomingBookingFormTour($tour) ? 'upcoming' : 'historical',
                'html' => view('admin.bookings._tour-option', [
                    'tour' => $tour,
                    'selectedTour' => '',
                    'showStatus' => ! $this->isUpcomingBookingFormTour($tour),
                ])->render(),
            ])->values(),
        ]);
    }

    public function exportCsv(Request $request)
    {
        $filename = 'bookings-export-'.now()->format('Ymd-His').'.csv';

        $rows = Booking::with(['tour', 'tour.tourType', 'languages'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = trim((string) $request->q);

                $query->where(function ($builder) use ($q) {
                    $builder->where('booking_name', 'like', "%{$q}%")
                        ->orWhere('contact_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->when($request->filled('date'), function ($query) use ($request) {
                $query->whereHas('tour', function ($builder) use ($request) {
                    $builder->whereDate('tour_date', $request->date);
                });
            })
            ->lazyByIdDesc(200);

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Bokning',
                'Kontakt',
                'Telefon',
                'Tur',
                'Turtyp',
                'Datum',
                'Språk',
                'Bokade',
                'Status',
                'Ankomststatus',
                'Walk-in',
                'Mat',
                'Faktureras',
            ], ';');

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->booking_name,
                    $row->contact_name,
                    $row->phone,
                    $row->tour?->title,
                    $row->tour?->tourType?->name,
                    $row->tour?->tour_date,
                    $row->languages->pluck('name')->implode(', '),
                    $row->total_count,
                    $row->status,
                    $row->arrival_status,
                    $row->is_walk_in ? 'Ja' : 'Nej',
                    $row->includes_meal ? 'Med mat' : 'Ej mat',
                    $row->to_be_invoiced ? 'Ja' : 'Nej',
                ], ';');
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function validated(Request $request, ?Booking $booking = null): array
    {
        return $request->validate([
            'tour_id' => 'required|exists:tours,id',
            'booking_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('bookings', 'booking_name')->ignore($booking?->id),
            ],
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'men_count' => 'nullable|integer|min:0',
            'women_count' => 'nullable|integer|min:0',
            'youth_count' => 'nullable|integer|min:0',
            'child_count' => 'nullable|integer|min:0',
            'unspecified_count' => 'nullable|integer|min:0',
            'participant_count' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'status' => 'required|in:preliminary,confirmed,cancelled,completed',
            'arrival_status' => 'nullable|in:booked,arrived,no_show,late_cancel',
            'is_walk_in' => 'nullable|boolean',
            'includes_meal' => 'nullable|boolean',
            'to_be_invoiced' => 'nullable|boolean',
            'languages' => 'nullable|array',
            'languages.*' => 'exists:languages,id',
            'country_id' => ['nullable', 'exists:countries,id'],
            'country_proposed_name' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function prepareBookingData(array $data, Request $request): array
    {
        $counts = $this->participants->normalize($data);
        $data = array_merge($data, $counts);

        $data['arrival_status'] = $data['arrival_status'] ?? 'booked';

        if ($request->boolean('is_walk_in')) {
            $data['is_walk_in'] = true;
            $data['arrival_status'] = 'arrived';
            $data['checked_in_at'] = now();
        } else {
            $data['is_walk_in'] = false;
        }

        $data['includes_meal'] = (string) $request->input('includes_meal', '0') === '1';
        $data['to_be_invoiced'] = $request->boolean('to_be_invoiced');

        $data['country_id'] = $this->countryProposalService->resolve(
            isset($data['country_id']) ? (int) $data['country_id'] : null,
            $data['country_proposed_name'] ?? null,
        );
        unset($data['country_proposed_name']);

        return $data;
    }

    /**
     * @return array{quickPickCountries: Collection<int, Country>, countries: Collection<int, Country>}
     */
    private function countryFormOptions(): array
    {
        $quickPickCountries = Country::query()
            ->active()
            ->quickPick()
            ->orderBy('name')
            ->take(CountryCatalog::maxQuickPicks())
            ->get();

        $countries = Country::query()
            ->active()
            ->when(
                $quickPickCountries->isNotEmpty(),
                fn ($query) => $query->whereNotIn('id', $quickPickCountries->modelKeys())
            )
            ->orderBy('name')
            ->get();

        return compact('quickPickCountries', 'countries');
    }

    private function resolveLanguageIds(Request $request): array
    {
        $languageIds = $request->input('languages', []);

        if (empty($languageIds)) {
            $languageIds = Language::defaultIds();
        }

        return array_map('intval', $languageIds);
    }

    private function generateBookingName(Tour $tour): string
    {
        do {
            $candidate = 'BOK-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));
        } while (Booking::where('booking_name', $candidate)->exists());

        return $candidate;
    }

    private function sumParticipantFields($men, $women, $youth, $child, $unspecified = 0): int
    {
        return (int) $men + (int) $women + (int) $youth + (int) $child + (int) $unspecified;
    }

    private function ensureTourCanBeBooked(Tour $tour, bool $moveContext = false): void
    {
        if ($tour->status !== 'cancelled') {
            return;
        }

        $message = $moveContext
            ? 'Det går inte att flytta eller ändra bokning till en avbokad tur.'
            : 'Det går inte att boka en avbokad tur.';

        throw ValidationException::withMessages([
            'tour_id' => $message,
        ]);
    }

    /**
     * @return array{upcoming: Collection<int, Tour>, historical: Collection<int, Tour>}
     */
    private function toursGroupedForBookingForm(?int $includeTourId = null): array
    {
        $today = now()->toDateString();
        $historyFrom = now()->subDays(14)->toDateString();
        $tomorrow = $this->tourDayLoad->nextCalendarDay($today);

        $upcoming = $this->bookingFormTourQuery()
            ->where('status', 'planned')
            ->where('tour_date', '>=', $today)
            ->orderBy('tour_date')
            ->orderBy('start_time')
            ->get();

        $historical = $this->bookingFormTourQuery()
            ->where('status', '!=', 'cancelled')
            ->where(function (Builder $query) use ($today, $historyFrom, $tomorrow) {
                $query->where(function (Builder $past) use ($historyFrom, $today) {
                    $past->where('tour_date', '>=', $historyFrom)
                        ->where('tour_date', '<', $today);
                })->orWhere(function (Builder $startedToday) use ($today, $tomorrow) {
                    $startedToday->whereIn('status', ['started', 'completed'])
                        ->where('tour_date', '>=', $today)
                        ->where('tour_date', '<', $tomorrow);
                });
            })
            ->orderByDesc('tour_date')
            ->orderByDesc('start_time')
            ->get();

        if ($includeTourId) {
            $alreadyListed = $upcoming->contains('id', $includeTourId)
                || $historical->contains('id', $includeTourId);

            if (! $alreadyListed) {
                $includedTour = $this->bookingFormTourQuery()->find($includeTourId);

                if ($includedTour && $includedTour->status !== 'cancelled') {
                    if ($this->isUpcomingBookingFormTour($includedTour)) {
                        $upcoming->push($includedTour);
                    } else {
                        $historical->prepend($includedTour);
                    }
                }
            }
        }

        return [
            'upcoming' => $upcoming,
            'historical' => $historical->unique('id')->values(),
        ];
    }

    /**
     * @return Builder<Tour>
     */
    private function bookingFormTourQuery(): Builder
    {
        return Tour::query()
            ->with('tourType:id,name')
            ->withSum([
                'bookings as booked_people_count' => fn ($query) => $query->whereNotIn('status', ['cancelled']),
            ], 'total_count');
    }

    private function isUpcomingBookingFormTour(Tour $tour): bool
    {
        return $tour->status === 'planned'
            && $tour->tour_date !== null
            && $tour->tour_date->toDateString() >= now()->toDateString();
    }

    private function allowsRetroactiveBooking(Tour $tour): bool
    {
        if ($tour->status === 'cancelled') {
            return false;
        }

        if (in_array($tour->status, ['completed', 'started'], true)) {
            return true;
        }

        return $tour->tour_date !== null
            && $tour->tour_date->toDateString() < now()->toDateString();
    }

    private function ensureBookingCanBeEdited(Booking $booking): void
    {
        $booking->loadMissing('tour');
    }

    private function hasDuplicateWarning(array $data, ?int $ignoreBookingId = null): bool
    {
        $query = Booking::query()
            ->when($ignoreBookingId, fn ($q) => $q->where('id', '!=', $ignoreBookingId))
            ->where(function ($q) use ($data) {
                if (! empty($data['booking_name'])) {
                    $q->where('booking_name', $data['booking_name']);
                }

                if (! empty($data['phone'])) {
                    $q->orWhere('phone', $data['phone']);
                }
            });

        if (! empty($data['tour_id'])) {
            $tour = Tour::find($data['tour_id']);

            if ($tour) {
                $query->whereHas('tour', function ($builder) use ($tour) {
                    $builder->whereDate('tour_date', $tour->tour_date);
                });
            }
        }

        return $query->exists();
    }

    private function sendBookingNotificationAfterStore(Booking $booking): void
    {
        if (! $booking->email) {
            return;
        }

        $booking->loadMissing('tour');

        if ($this->allowsRetroactiveBooking($booking->tour)) {
            return;
        }

        app(NotificationService::class)
            ->sendBookingConfirmation($booking->fresh(['tour.guide', 'tour.tourType', 'languages']));
    }

    private function sendBookingNotificationAfterUpdate(Booking $booking, ?string $oldStatus = null): void
    {
        if (! $booking->email) {
            return;
        }

        $booking->loadMissing('tour');

        if ($booking->tour && $this->allowsRetroactiveBooking($booking->tour)) {
            return;
        }

        $freshBooking = $booking->fresh(['tour.guide', 'tour.tourType', 'languages']);

        if ($freshBooking->status === 'cancelled' && $oldStatus !== 'cancelled') {
            app(NotificationService::class)->sendBookingCancelled($freshBooking);

            return;
        }

        if ($freshBooking->status !== 'cancelled') {
            app(NotificationService::class)->sendBookingUpdated($freshBooking);
        }
    }

    private function sendBookingUpdatedNotification(Booking $booking): void
    {
        if (! $booking->email || $booking->status === 'cancelled') {
            return;
        }

        $booking->loadMissing('tour');

        if ($booking->tour && $this->allowsRetroactiveBooking($booking->tour)) {
            return;
        }

        app(NotificationService::class)
            ->sendBookingUpdated($booking->fresh(['tour.guide', 'tour.tourType', 'languages']));
    }

    private function redirectAfterBookingChange(Tour $tour, string $message): RedirectResponse
    {
        $params = $this->allowsRetroactiveBooking($tour) ? ['scope' => 'archive'] : [];

        return redirect()
            ->route($this->routePrefix().'.bookings.index', $params)
            ->with('success', $message);
    }

    /**
     * @param  Builder<Booking>  $query
     */
    private function applyBookingSearch($query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $normalizedSearch = mb_strtolower($search);

        if (in_array($normalizedSearch, ['faktureras', 'faktura'], true)) {
            $query->where('to_be_invoiced', true);

            return;
        }

        $query->where(function ($builder) use ($search) {
            $builder->where('booking_name', 'like', "%{$search}%")
                ->orWhere('contact_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%");
        });
    }

    /**
     * @param  list<int>  $languageIds
     */
    private function redirectWithBookingGuideLanguageWarning(
        RedirectResponse $redirect,
        Tour $tour,
        array $languageIds,
        ?string $additionalWarning = null,
    ): RedirectResponse {
        $warnings = array_filter([
            $this->guideLanguageMatchService->bookingGuideLanguageWarning(
                $tour->fresh(['guide.guideLanguages']),
                $this->languageCodesFromIds($languageIds)
            ),
            $additionalWarning,
        ]);

        return $warnings !== []
            ? $redirect->with('warning', implode(' ', $warnings))
            : $redirect;
    }

    /**
     * @param  list<int>  $languageIds
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

    private function redirectAfterQuickParticipantUpdate(
        Request $request,
        Tour $tour,
        bool $returnToFollowUp,
    ): RedirectResponse {
        if ($returnToFollowUp) {
            return redirect()
                ->route($this->routePrefix().'.statistics.unspecified-follow-up', array_filter([
                    'from' => $request->input('follow_up_from'),
                    'to' => $request->input('follow_up_to'),
                    'scope' => $request->input('follow_up_scope', 'upcoming'),
                    'page' => $request->input('follow_up_page'),
                ], fn ($value) => $value !== null && $value !== ''))
                ->with('success', 'Fördelning sparad.');
        }

        return redirect()
            ->route($this->routePrefix().'.tours.show', $tour)
            ->with('success', 'Bokningen uppdaterades.');
    }

    private function routePrefix(): string
    {
        return ActiveRole::routePrefix();
    }
}
