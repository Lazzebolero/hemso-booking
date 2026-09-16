<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Language;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Services\BookingParticipantService;
use App\Services\LogService;
use App\Services\TourAutoCompleteService;
use App\Services\TourDurationSettingsService;
use App\Support\ActiveRole;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class GroupBookingController extends Controller
{
    public function __construct(
        private BookingParticipantService $participants,
        private TourDurationSettingsService $durationSettings,
        private TourAutoCompleteService $autoComplete,
    ) {}

    public function create(): View
    {
        $tourTypes = TourType::activeOrdered();
        $defaultTourTypeId = $this->durationSettings->defaultTourTypeId();

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

        $defaultLanguageIds = Language::defaultIds();

        return view('admin.group-bookings.create', compact(
            'tourTypes',
            'defaultTourTypeId',
            'guides',
            'languages',
            'defaultLanguageIds',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'tour_type_id' => ['required', 'exists:tour_types,id'],
            'tour_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'guide_id' => ['nullable', 'exists:users,id'],
            'men_count' => ['nullable', 'integer', 'min:0'],
            'women_count' => ['nullable', 'integer', 'min:0'],
            'youth_count' => ['nullable', 'integer', 'min:0'],
            'child_count' => ['nullable', 'integer', 'min:0'],
            'unspecified_count' => ['nullable', 'integer', 'min:0'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'includes_meal' => ['nullable', 'boolean'],
            'language_ids' => ['nullable', 'array'],
            'language_ids.*' => ['integer', 'exists:languages,id'],
        ], [
            'title.required' => 'Ange ett namn på turen.',
            'tour_type_id.required' => 'Välj en turtyp.',
            'tour_date.required' => 'Ange datum.',
            'start_time.required' => 'Ange starttid.',
        ]);

        $counts = $this->participants->normalize($data);
        $includesMeal = (string) $request->input('includes_meal', '0') === '1';
        $tourTypeId = (int) $data['tour_type_id'];
        $startTime = $data['start_time'];
        $endTime = filled($data['end_time'] ?? null)
            ? $data['end_time'].':00'
            : $this->durationSettings->endTimeFromStartTime($startTime, $tourTypeId);

        $startAt = Carbon::parse($data['tour_date'].' '.$startTime.':00');
        $normalizedEnd = strlen($endTime) === 5 ? $endTime.':00' : $endTime;
        $endAt = Carbon::parse($data['tour_date'].' '.$normalizedEnd);

        if ($endAt->lte($startAt)) {
            $endAt = $startAt->copy()->addMinutes($this->durationSettings->durationMinutes($tourTypeId));
            $normalizedEnd = $endAt->format('H:i:s');
        }

        $capacity = $this->durationSettings->resolveCapacityForQuickTour($counts['total_count']);

        $languageIds = collect($data['language_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($languageIds === []) {
            $languageIds = Language::defaultIds();
        }

        $tour = Tour::query()->create([
            'title' => trim($data['title']),
            'tour_type_id' => $tourTypeId,
            'tour_date' => $data['tour_date'],
            'start_time' => $startTime.':00',
            'end_time' => $normalizedEnd,
            'baseline_end_time' => $this->autoComplete->captureBaselineEndTime($normalizedEnd),
            'status' => 'planned',
            'guide_id' => filled($data['guide_id'] ?? null) ? (int) $data['guide_id'] : null,
            'max_participants' => $capacity,
            'default_includes_meal' => $includesMeal,
            'exclude_from_booking_sequence' => false,
            'exclude_from_schedule_statistics' => false,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $booking = Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => $tour->title,
            'contact_name' => filled($data['contact_name'] ?? null)
                ? trim((string) $data['contact_name'])
                : $tour->title,
            'men_count' => $counts['men_count'],
            'women_count' => $counts['women_count'],
            'youth_count' => $counts['youth_count'],
            'child_count' => $counts['child_count'],
            'unspecified_count' => $counts['unspecified_count'],
            'total_count' => $counts['total_count'],
            'notes' => $data['notes'] ?? null,
            'status' => 'confirmed',
            'includes_meal' => $includesMeal,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $booking->languages()->sync($languageIds);

        LogService::log(
            'tour',
            $tour->id,
            'created',
            null,
            $tour->toArray(),
            'Skapade gruppbokning'
        );

        LogService::log(
            'booking',
            $booking->id,
            'created',
            null,
            $booking->fresh(['languages'])->toArray(),
            'Skapade bokning via gruppbokning'
        );

        $prefix = ActiveRole::routePrefix();

        return redirect()
            ->route($prefix.'.tours.show', $tour)
            ->with('success', 'Gruppbokning skapad.');
    }
}
