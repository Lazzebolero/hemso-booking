<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Language;
use App\Models\Tour;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
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
            $q = trim((string) $request->q);

            $query->where(function ($builder) use ($q) {
                $builder->where('booking_name', 'like', "%{$q}%")
                    ->orWhere('contact_name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($request->filled('date')) {
            $query->whereHas('tour', function ($builder) use ($request) {
                $builder->whereDate('tour_date', $request->date);
            });
        }

        if ($request->filled('arrival_status')) {
            $query->where('arrival_status', $request->arrival_status);
        }

        if ($request->boolean('waitlist_only')) {
            $query->where('is_waitlist', true);
        }

        $bookings = $query->latest()->paginate(20)->withQueryString();

        return view('admin.bookings.index', compact('bookings', 'scope'));
    }

    public function create()
    {
        $tours = Tour::with(['bookings', 'tourType'])
            ->whereDate('tour_date', '>=', now()->toDateString())
            ->where('status', 'planned')
            ->orderBy('tour_date')
            ->orderBy('start_time')
            ->get();

        $languages = Language::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.bookings.create', [
            'booking' => new Booking(),
            'tours' => $tours,
            'languages' => $languages,
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
        $data['is_waitlist'] = $this->shouldGoToWaitlist($tour, $data['total_count']);

        if (!$data['is_waitlist']) {
            $this->ensureCapacity($tour, $data['total_count']);
        }

        $booking = null;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                if (blank($data['booking_name'] ?? null)) {
                    $data['booking_name'] = $this->generateBookingName($tour);
                }

                $booking = Booking::create($data);
                $booking->languages()->sync($this->resolveLanguageIds($request));

                break;
            } catch (\Illuminate\Database\QueryException $e) {
                $isDuplicate =
                    str_contains($e->getMessage(), 'Duplicate entry') ||
                    str_contains($e->getMessage(), 'booking_name');

                if (!$isDuplicate) {
                    throw $e;
                }

                $data['booking_name'] = $this->generateBookingName($tour);
            }
        }

        if (!$booking) {
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

        return redirect()
            ->route('admin.bookings.index')
            ->with('success', $booking->is_waitlist ? 'Bokning skapad i väntelista.' : 'Bokning skapad.');
    }

    public function edit(Booking $booking)
    {
        $this->ensureBookingCanBeEdited($booking);

        $currentTourId = $booking->tour_id;

        $tours = Tour::with(['bookings', 'tourType'])
            ->where(function ($query) use ($currentTourId) {
                $query->where(function ($q) {
                    $q->whereDate('tour_date', '>=', now()->toDateString())
                        ->where('status', 'planned');
                });

                if ($currentTourId) {
                    $query->orWhere('id', $currentTourId);
                }
            })
            ->orderBy('tour_date')
            ->orderBy('start_time')
            ->get();

        $languages = Language::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $booking->load(['notificationLogs' => fn ($q) => $q->latest()]);

        return view('admin.bookings.edit', compact('booking', 'tours', 'languages'));
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
        $data['is_waitlist'] = $this->shouldGoToWaitlist($tour, $data['total_count'], $booking->id);

        if (!$data['is_waitlist']) {
            $this->ensureCapacity($tour, $data['total_count'], $booking->id);
        }

        $booking->update($data);
        $booking->languages()->sync($this->resolveLanguageIds($request));

        LogService::log(
            'booking',
            $booking->id,
            'updated',
            $old,
            $booking->fresh(['languages'])->toArray(),
            'Uppdaterade bokning'
        );

        $this->sendBookingNotificationAfterUpdate($booking, $oldStatus);

        return redirect()
            ->route('admin.bookings.index')
            ->with('success', 'Bokning uppdaterad.');
    }

    public function destroy(Booking $booking)
    {
        $old = $booking->toArray();

        $booking->delete();

        LogService::log(
            'booking',
            $booking->id,
            'deleted',
            $old,
            null,
            'Tog bort bokning'
        );

        return back()->with('success', 'Bokning borttagen.');
    }

    public function move(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'new_tour_id' => ['required', 'exists:tours,id'],
        ]);

        $newTour = Tour::findOrFail($data['new_tour_id']);

        $this->ensureTourCanBeBooked($newTour, true);
        $this->ensureCapacity($newTour, $booking->total_count);

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

        return back()->with('success', 'Bokningen flyttades.');
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

    public function quickUpdateParticipants(Booking $booking, Request $request)
    {
        $this->ensureBookingCanBeEdited($booking);

        $oldStatus = $booking->status;

        $data = $request->validate([
            'men_count' => ['required', 'integer', 'min:0'],
            'women_count' => ['required', 'integer', 'min:0'],
            'youth_count' => ['required', 'integer', 'min:0'],
            'child_count' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:preliminary,confirmed,cancelled,completed'],
        ]);

        $tour = $booking->tour;

        $data['total_count'] = $this->sumParticipantFields(
            $data['men_count'],
            $data['women_count'],
            $data['youth_count'],
            $data['child_count']
        );

        $this->ensureCapacity($tour, $data['total_count'], $booking->id);

        $data['updated_by'] = auth()->id();

        $booking->update($data);

        LogService::log(
            'booking',
            $booking->id,
            'quick_updated',
            null,
            $booking->fresh()->toArray(),
            'Uppdaterade deltagare direkt i turvyn'
        );

        $this->sendBookingNotificationAfterUpdate($booking, $oldStatus);

        return redirect()
            ->route('admin.tours.show', $tour)
            ->with('success', 'Bokningen uppdaterades.');
    }

    public function exportCsv(Request $request)
    {
        $filename = 'bookings-export-' . now()->format('Ymd-His') . '.csv';

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
            ->latest()
            ->get();

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

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
                'Väntelista',
                'Walk-in',
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
                    $row->is_waitlist ? 'Ja' : 'Nej',
                    $row->is_walk_in ? 'Ja' : 'Nej',
                ], ';');
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
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
            'men_count' => 'required|integer|min:0',
            'women_count' => 'required|integer|min:0',
            'youth_count' => 'required|integer|min:0',
            'child_count' => 'required|integer|min:0',
            'notes' => 'nullable|string',
            'status' => 'required|in:preliminary,confirmed,cancelled,completed',
            'arrival_status' => 'nullable|in:booked,arrived,no_show,late_cancel',
            'is_walk_in' => 'nullable|boolean',
            'languages' => 'nullable|array',
            'languages.*' => 'exists:languages,id',
        ]);
    }

    private function prepareBookingData(array $data, Request $request): array
    {
        $data['total_count'] = $this->sumParticipantFields(
            $data['men_count'],
            $data['women_count'],
            $data['youth_count'],
            $data['child_count']
        );

        $data['arrival_status'] = $data['arrival_status'] ?? 'booked';

        if ($request->boolean('is_walk_in')) {
            $data['is_walk_in'] = true;
            $data['arrival_status'] = 'arrived';
            $data['checked_in_at'] = now();
        } else {
            $data['is_walk_in'] = false;
        }

        return $data;
    }

    private function resolveLanguageIds(Request $request): array
    {
        $languageIds = $request->input('languages', []);

        if (empty($languageIds)) {
            $defaultLanguageId = Language::where('is_default', true)->value('id');
            if ($defaultLanguageId) {
                $languageIds = [$defaultLanguageId];
            }
        }

        return array_map('intval', $languageIds);
    }

    private function generateBookingName(Tour $tour): string
    {
        do {
            $candidate = 'BOK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
        } while (Booking::where('booking_name', $candidate)->exists());

        return $candidate;
    }

    private function sumParticipantFields($men, $women, $youth, $child): int
    {
        return (int) $men + (int) $women + (int) $youth + (int) $child;
    }

    private function ensureTourCanBeBooked(Tour $tour, bool $moveContext = false): void
    {
        if ($tour->status === 'completed') {
            $message = $moveContext
                ? 'Det går inte att flytta eller ändra bokning till en avslutad tur.'
                : 'Det går inte att boka en avslutad tur.';

            throw ValidationException::withMessages([
                'tour_id' => $message,
            ]);
        }
    }

    private function ensureBookingCanBeEdited(Booking $booking): void
    {
        if ($booking->tour && $booking->tour->status === 'completed') {
            throw ValidationException::withMessages([
                'booking' => 'Det går inte att ändra bokningar på en avslutad tur.',
            ]);
        }
    }

    private function currentBookedCount(Tour $tour, ?int $ignoreBookingId = null): int
    {
        return (int) Booking::where('tour_id', $tour->id)
            ->when($ignoreBookingId, fn ($q) => $q->where('id', '!=', $ignoreBookingId))
            ->whereNotIn('status', ['cancelled'])
            ->where('is_waitlist', false)
            ->sum('total_count');
    }

    private function shouldGoToWaitlist(Tour $tour, int $newCount, ?int $ignoreBookingId = null): bool
    {
        return ($this->currentBookedCount($tour, $ignoreBookingId) + $newCount) > $tour->max_participants;
    }

    private function ensureCapacity(Tour $tour, int $newCount, ?int $ignoreBookingId = null): void
    {
        $existing = $this->currentBookedCount($tour, $ignoreBookingId);

        if (($existing + $newCount) > $tour->max_participants) {
            throw ValidationException::withMessages([
                'tour_id' => 'Turen är full. Bokningen bör läggas i väntelista eller flyttas.',
            ]);
        }
    }

    private function hasDuplicateWarning(array $data, ?int $ignoreBookingId = null): bool
    {
        $query = Booking::query()
            ->when($ignoreBookingId, fn ($q) => $q->where('id', '!=', $ignoreBookingId))
            ->where(function ($q) use ($data) {
                if (!empty($data['booking_name'])) {
                    $q->where('booking_name', $data['booking_name']);
                }

                if (!empty($data['phone'])) {
                    $q->orWhere('phone', $data['phone']);
                }
            });

        if (!empty($data['tour_id'])) {
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
        if (!$booking->email || $booking->is_waitlist) {
            return;
        }

        app(NotificationService::class)
            ->sendBookingConfirmation($booking->fresh(['tour.guide', 'tour.tourType', 'languages']));
    }

    private function sendBookingNotificationAfterUpdate(Booking $booking, ?string $oldStatus = null): void
    {
        if (!$booking->email) {
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
        if (!$booking->email || $booking->status === 'cancelled') {
            return;
        }

        app(NotificationService::class)
            ->sendBookingUpdated($booking->fresh(['tour.guide', 'tour.tourType', 'languages']));
    }
}