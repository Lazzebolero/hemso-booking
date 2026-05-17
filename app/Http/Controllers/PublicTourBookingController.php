<?php

namespace App\Http\Controllers;

use App\Mail\TemplatedMail;
use App\Models\Booking;
use App\Models\Language;
use App\Models\TourBookingPage;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicTourBookingController extends Controller
{
    public function upcomingSpecialTours(Request $request): JsonResponse
    {
        $tours = TourBookingPage::query()
            ->with([
                'tour' => function ($query): void {
                    $query
                        ->with('tourType')
                        ->withSum([
                            'bookings as booked_participants_count' => function ($query): void {
                                $query
                                    ->whereNotIn('status', ['cancelled'])
                                    ->where('is_waitlist', false);
                            },
                        ], 'total_count');
                },
            ])
            ->where('is_public', true)
            ->whereHas('tour', function ($query): void {
                $query
                    ->whereDate('tour_date', '>=', now()->toDateString())
                    ->whereNotIn('status', ['cancelled', 'completed']);
            })
            ->get()
            ->filter(fn (TourBookingPage $bookingPage): bool => $bookingPage->tour !== null)
            ->sortBy(fn (TourBookingPage $bookingPage): string => ($bookingPage->tour->tour_date?->format('Y-m-d') ?? '').' '.substr((string) $bookingPage->tour->start_time, 0, 5))
            ->values()
            ->map(function (TourBookingPage $bookingPage) use ($request): array {
                $tour = $bookingPage->tour;
                $bookedParticipants = (int) ($tour->booked_participants_count ?? 0);
                $capacity = (int) $tour->max_participants;
                $availableSpots = max(0, $capacity - $bookedParticipants);
                $bookingPath = route('public.tour-booking.show', $bookingPage->slug, false);

                return [
                    'id' => $tour->id,
                    'title' => $tour->title,
                    'description' => $tour->description,
                    'tour_type' => $tour->tourType?->name,
                    'date' => $tour->tour_date?->format('Y-m-d'),
                    'start_time' => substr((string) $tour->start_time, 0, 5),
                    'end_time' => $tour->end_time ? substr((string) $tour->end_time, 0, 5) : null,
                    'capacity' => $capacity,
                    'booked_participants' => $bookedParticipants,
                    'available_spots' => $availableSpots,
                    'is_full' => $availableSpots <= 0,
                    'page_title' => $bookingPage->page_title,
                    'page_text' => $bookingPage->page_text,
                    'prices' => [
                        'adult' => (float) $bookingPage->adult_price,
                        'youth' => (float) $bookingPage->youth_price,
                        'child' => (float) $bookingPage->child_price,
                    ],
                    'booking_path' => $bookingPath,
                    'booking_url' => $request->getSchemeAndHttpHost().$bookingPath,
                    'updated_at' => $bookingPage->updated_at?->toIso8601String(),
                ];
            });

        return response()
            ->json([
                'data' => $tours,
                'meta' => [
                    'count' => $tours->count(),
                    'generated_at' => now()->toIso8601String(),
                ],
            ])
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function show(string $slug)
    {
        $bookingPage = TourBookingPage::with([
            'tour.guide',
            'tour.tourType',
            'tour.bookings.languages',
        ])
            ->where('slug', $slug)
            ->where('is_public', true)
            ->firstOrFail();

        $tour = $bookingPage->tour;

        abort_if(! $tour, 404);

        $isFull = $this->isTourFull((int) $tour->id, (int) $tour->max_participants);

        return view('public.tour-booking.show', compact(
            'bookingPage',
            'tour',
            'isFull'
        ));
    }

    public function store(Request $request, string $slug)
    {
        $bookingPage = TourBookingPage::with('tour')
            ->where('slug', $slug)
            ->where('is_public', true)
            ->firstOrFail();

        $tour = $bookingPage->tour;

        abort_if(! $tour, 404);

        if ($tour->status === 'completed') {
            throw ValidationException::withMessages([
                'booking' => 'Det går inte att boka en avslutad tur.',
            ]);
        }

        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'men_count' => ['required', 'integer', 'min:0'],
            'women_count' => ['required', 'integer', 'min:0'],
            'youth_count' => ['required', 'integer', 'min:0'],
            'child_count' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'accept_terms' => ['accepted'],
        ]);

        $totalCount =
            (int) $data['men_count'] +
            (int) $data['women_count'] +
            (int) $data['youth_count'] +
            (int) $data['child_count'];

        if ($totalCount <= 0) {
            throw ValidationException::withMessages([
                'men_count' => 'Ange minst 1 deltagare.',
            ]);
        }

        $currentBooked = $this->currentBookedCount((int) $tour->id);

        if (($currentBooked + $totalCount) > (int) $tour->max_participants) {
            return redirect()
                ->route('public.tour-booking.show', $bookingPage->slug)
                ->withErrors([
                    'booking' => $bookingPage->full_tour_text ?: 'Denna tur är fullbokad.',
                ])
                ->withInput();
        }

        $booking = Booking::create([
            'tour_id' => $tour->id,
            'booking_name' => $this->generateBookingName(),
            'contact_name' => $data['contact_name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'men_count' => (int) $data['men_count'],
            'women_count' => (int) $data['women_count'],
            'youth_count' => (int) $data['youth_count'],
            'child_count' => (int) $data['child_count'],
            'total_count' => $totalCount,
            'notes' => $data['notes'] ?? null,
            'status' => 'confirmed',
            'arrival_status' => 'booked',
            'is_waitlist' => false,
            'is_walk_in' => false,
            'duplicate_warning' => false,
        ]);

        $defaultLanguageId = Language::where('is_default', true)->value('id');
        if ($defaultLanguageId) {
            $booking->languages()->sync([$defaultLanguageId]);
        }

        $this->sendSpecialConfirmationMail($booking, $bookingPage);

        return redirect()
            ->route('public.tour-booking.thank-you', $bookingPage->slug);
    }

    public function thankYou(string $slug)
    {
        $bookingPage = TourBookingPage::with('tour')
            ->where('slug', $slug)
            ->where('is_public', true)
            ->firstOrFail();

        return view('public.tour-booking.thank-you', compact('bookingPage'));
    }

    private function currentBookedCount(int $tourId): int
    {
        return (int) Booking::where('tour_id', $tourId)
            ->whereNotIn('status', ['cancelled'])
            ->where('is_waitlist', false)
            ->sum('total_count');
    }

    private function isTourFull(int $tourId, int $maxParticipants): bool
    {
        return $this->currentBookedCount($tourId) >= $maxParticipants;
    }

    private function generateBookingName(): string
    {
        do {
            $candidate = 'BOK-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));
        } while (Booking::where('booking_name', $candidate)->exists());

        return $candidate;
    }

    private function sendSpecialConfirmationMail(Booking $booking, TourBookingPage $bookingPage): void
    {
        if (! $booking->email) {
            return;
        }

        $tour = $bookingPage->tour;

        if (! $tour) {
            return;
        }

        $subject = $this->replaceVariables(
            $bookingPage->confirmation_subject ?: 'Bokningsbekräftelse',
            $booking,
            $tour
        );

        $body = $this->replaceVariables(
            $bookingPage->confirmation_body ?: 'Tack för din bokning.',
            $booking,
            $tour
        );

        Mail::to($booking->email)->send(
            new TemplatedMail($subject, nl2br(e($body)))
        );
    }

    private function replaceVariables(string $text, Booking $booking, $tour): string
    {
        $tourDate = '';
        if (! empty($tour->tour_date)) {
            try {
                $tourDate = Carbon::parse($tour->tour_date)->format('Y-m-d');
            } catch (\Throwable $e) {
                $tourDate = (string) $tour->tour_date;
            }
        }

        $startTime = ! empty($tour->start_time)
            ? substr((string) $tour->start_time, 0, 5)
            : '';

        $replacements = [
            '{{contact_name}}' => (string) ($booking->contact_name ?? ''),
            '{{tour_title}}' => (string) ($tour->title ?? ''),
            '{{tour_date}}' => $tourDate,
            '{{start_time}}' => $startTime,
            '{{total_count}}' => (string) ($booking->total_count ?? 0),
            '{{booking_name}}' => (string) ($booking->booking_name ?? ''),
            '{{phone}}' => (string) ($booking->phone ?? ''),
            '{{email}}' => (string) ($booking->email ?? ''),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $text
        );
    }
}
