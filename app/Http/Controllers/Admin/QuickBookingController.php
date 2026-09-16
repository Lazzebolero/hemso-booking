<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Country;
use App\Models\Language;
use App\Models\Tour;
use App\Services\BookingInvoiceNotificationService;
use App\Services\BookingParticipantService;
use App\Services\CountryProposalService;
use App\Services\GuideLanguageMatchService;
use App\Services\LogService;
use App\Services\TourBookingSequenceService;
use App\Support\ActiveRole;
use App\Support\CountryCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuickBookingController extends Controller
{
    public function __construct(
        private BookingParticipantService $participants,
        private GuideLanguageMatchService $guideLanguageMatchService,
        private TourBookingSequenceService $bookingSequence,
        private BookingInvoiceNotificationService $bookingInvoiceNotifications,
        private CountryProposalService $countryProposalService,
    ) {}

    public function create()
    {
        $today = now()->toDateString();
        $nowTime = now()->format('H:i:s');

        $tours = $this->bookingSequence->applyEligibleScope(
            Tour::query()
                ->from('tours')
                ->with([
                    'guide',
                    'tourType',
                    'bookings.languages',
                ])
        )
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
            'defaultLanguageId',
            'countries',
            'quickPickCountries'
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
            'country_id' => ['nullable', 'exists:countries,id'],
            'country_proposed_name' => ['nullable', 'string', 'max:255'],
            'includes_meal' => ['nullable', 'boolean'],
            'to_be_invoiced' => ['nullable', 'boolean'],
        ]);

        $tour = $this->bookingSequence->applyEligibleScope(
            Tour::query()->from('tours')->with('bookings')
        )->findOrFail($data['tour_id']);

        $counts = $this->participants->normalize($data);
        $countryId = $this->countryProposalService->resolve(
            isset($data['country_id']) ? (int) $data['country_id'] : null,
            $data['country_proposed_name'] ?? null,
        );

        $booking = Booking::create([
            'tour_id' => $tour->id,
            'booking_name' => $this->generateBookingName(),
            'contact_name' => $data['contact_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'country_id' => $countryId,
            'men_count' => $counts['men_count'],
            'women_count' => $counts['women_count'],
            'youth_count' => $counts['youth_count'],
            'child_count' => $counts['child_count'],
            'unspecified_count' => $counts['unspecified_count'],
            'total_count' => $counts['total_count'],
            'notes' => $data['notes'] ?? null,
            'status' => 'confirmed',
            'arrival_status' => 'booked',
            'is_waitlist' => false,
            'is_walk_in' => false,
            'includes_meal' => (string) $request->input('includes_meal', '0') === '1',
            'to_be_invoiced' => $request->boolean('to_be_invoiced'),
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
            $booking->fresh(['languages', 'country'])->toArray(),
            'Skapade bokning via bokningssekvens'
        );

        $invoiceWarning = $this->bookingInvoiceNotifications->notifyAfterStore($booking);

        $redirect = redirect()
            ->route(ActiveRole::routePrefix().'.bookings.quick-create')
            ->with('success', 'Bokning skapad.');

        $warnings = array_filter([
            $this->guideLanguageMatchService->bookingGuideLanguageWarning(
                $tour->fresh(['guide.guideLanguages']),
                $this->languageCodesFromIds($languageIds)
            ),
            $invoiceWarning,
        ]);

        return $warnings !== []
            ? $redirect->with('warning', implode(' ', $warnings))
            : $redirect;
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

    private function generateBookingName(): string
    {
        do {
            $candidate = 'BOK-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));
        } while (Booking::where('booking_name', $candidate)->exists());

        return $candidate;
    }
}
