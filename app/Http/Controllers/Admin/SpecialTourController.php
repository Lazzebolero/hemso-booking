<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\TourBookingPage;
use App\Models\TourType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Support\ActiveRole;
use App\Support\Roles;
use App\Models\WorkShift;

class SpecialTourController extends Controller
{
    public function index()
    {
        $tours = Tour::with(['guide', 'tourType', 'bookingPage'])
            ->whereHas('bookingPage')
            ->orderBy('tour_date')
            ->orderBy('start_time')
            ->paginate(20);

        return view('admin.special-tours.index', compact('tours'));
    }

   public function create()
{
    $tour = new Tour([
        'tour_date' => now()->toDateString(),
        'start_time' => '11:00',
        'max_participants' => (int) setting('default_tour_capacity', 25),
        'status' => 'planned',
    ]);

    $bookingPage = new TourBookingPage([
        'page_title' => '',
        'page_text' => '',
        'thank_you_text' => 'Tack för din bokning.',
        'full_tour_text' => 'Denna tur är fullbokad.',
        'booking_terms' => 'Bokningen är bindande enligt angivna villkor.',
        'adult_price' => 0,
        'youth_price' => 0,
        'child_price' => 0,
        'confirmation_subject' => 'Bokningsbekräftelse',
        'confirmation_body' => "Hej {{contact_name}},\n\nTack för din bokning till {{tour_title}} den {{tour_date}} kl {{start_time}}.\nAntal personer: {{total_count}}.\n\nVälkommen!",
        'is_public' => true,
    ]);

    $guides = User::query()
        ->whereHas('roles', function ($query) {
            $query->where('slug', Roles::GUIDE);
        })
        ->with(['workShifts' => function ($query) use ($tour) {
            $query->whereDate('shift_date', $tour->tour_date)
                ->where('shift_role', Roles::GUIDE)
                ->whereNotIn('status', ['cancelled'])
                ->orderBy('start_time');
        }])
        ->orderBy('name')
        ->get();

    $tourTypes = TourType::where('is_active', true)->orderBy('name')->get();
    $defaultTourTypeId = TourType::where('is_default', true)->value('id');

    return view('admin.special-tours.create', compact(
        'tour',
        'bookingPage',
        'guides',
        'tourTypes',
        'defaultTourTypeId'
    ));
}

   public function edit(Tour $tour)
{
    $tour->load('bookingPage');

    abort_unless($tour->bookingPage, 404);

    $bookingPage = $tour->bookingPage;

    $guides = User::query()
        ->whereHas('roles', function ($query) {
            $query->where('slug', Roles::GUIDE);
        })
        ->with(['workShifts' => function ($query) use ($tour) {
            $query->whereDate('shift_date', $tour->tour_date)
                ->where('shift_role', Roles::GUIDE)
                ->whereNotIn('status', ['cancelled'])
                ->orderBy('start_time');
        }])
        ->orderBy('name')
        ->get();

    $tourTypes = TourType::where('is_active', true)->orderBy('name')->get();
    $defaultTourTypeId = TourType::where('is_default', true)->value('id');

    $publicUrl = $bookingPage->slug
        ? route('public.tour-booking.show', $bookingPage->slug)
        : null;

    return view('admin.special-tours.edit', compact(
        'tour',
        'bookingPage',
        'guides',
        'tourTypes',
        'defaultTourTypeId',
        'publicUrl'
    ));
}

    public function update(Request $request, Tour $tour)
    {
        $tour->load('bookingPage');

        abort_unless($tour->bookingPage, 404);

        $data = $this->validated($request, $tour->bookingPage->id);

        $tourData = $this->extractTourData($data);
        $bookingPageData = $this->extractBookingPageData($data);

        $tourData['end_time'] = $this->resolveEndTime(
            $tourData['start_time'] ?? null,
            $tourData['end_time'] ?? null,
            $tourData['tour_type_id'] ?? null
        );

        if (blank($tourData['title'] ?? null)) {
            $tourData['title'] = $this->generateTourTitle($tourData);
        }

        $tourData['updated_by'] = auth()->id();

        $tour->update($tourData);

        $tour->bookingPage->fill($bookingPageData);
        $tour->bookingPage->save();

        return redirect()
            ->route('admin.special-tours.edit', $tour)
            ->with('success', 'Specialtur uppdaterad.');
    }

    private function validated(Request $request, ?int $bookingPageId = null): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'tour_type_id' => ['nullable', 'exists:tour_types,id'],
            'tour_date' => ['required', 'date'],
            'start_time' => ['required'],
            'end_time' => ['nullable'],
            'max_participants' => ['required', 'integer', 'min:1'],
            'guide_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', 'in:planned,started,completed,cancelled'],
            'description' => ['nullable', 'string'],

            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tour_booking_pages', 'slug')->ignore($bookingPageId),
            ],
            'page_title' => ['required', 'string', 'max:255'],
            'page_text' => ['nullable', 'string'],
            'thank_you_text' => ['nullable', 'string'],
            'full_tour_text' => ['nullable', 'string'],
            'booking_terms' => ['nullable', 'string'],
            'adult_price' => ['required', 'numeric', 'min:0'],
            'youth_price' => ['required', 'numeric', 'min:0'],
            'child_price' => ['required', 'numeric', 'min:0'],
            'confirmation_subject' => ['nullable', 'string', 'max:255'],
            'confirmation_body' => ['nullable', 'string'],
            'is_public' => ['nullable', 'boolean'],
        ]);
    }

    private function extractTourData(array $data): array
    {
        return [
            'title' => $data['title'] ?? null,
            'tour_type_id' => $data['tour_type_id'] ?? null,
            'tour_date' => $data['tour_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'] ?? null,
            'max_participants' => (int) $data['max_participants'],
            'guide_id' => $data['guide_id'] ?? null,
            'status' => $data['status'],
            'description' => $data['description'] ?? null,
        ];
    }

    private function extractBookingPageData(array $data): array
    {
        return [
            'slug' => Str::slug($data['slug']),
            'page_title' => $data['page_title'],
            'page_text' => $data['page_text'] ?? null,
            'thank_you_text' => $data['thank_you_text'] ?? null,
            'full_tour_text' => $data['full_tour_text'] ?? null,
            'booking_terms' => $data['booking_terms'] ?? null,
            'adult_price' => $data['adult_price'],
            'youth_price' => $data['youth_price'],
            'child_price' => $data['child_price'],
            'confirmation_subject' => $data['confirmation_subject'] ?? null,
            'confirmation_body' => $data['confirmation_body'] ?? null,
            'is_public' => request()->boolean('is_public'),
        ];
    }

    private function resolveEndTime(?string $startTime, ?string $endTime = null, $tourTypeId = null): ?string
    {
        if (!$startTime) {
            return $endTime;
        }

        if (!empty($endTime)) {
            return $endTime;
        }

        $duration = 80;

        if ($tourTypeId) {
            $typeDuration = TourType::where('id', $tourTypeId)->value('default_duration_minutes');
            if ($typeDuration) {
                $duration = (int) $typeDuration;
            }
        }

        return Carbon::createFromFormat('H:i', substr($startTime, 0, 5))
            ->addMinutes($duration)
            ->format('H:i');
    }

    private function generateTourTitle(array $data): string
    {
        $typeName = 'Specialtur';

        if (!empty($data['tour_type_id'])) {
            $type = TourType::find($data['tour_type_id']);
            if ($type) {
                $typeName = $type->name;
            }
        }

        $date = !empty($data['tour_date'])
            ? date('Y-m-d', strtotime($data['tour_date']))
            : now()->toDateString();

        $time = !empty($data['start_time']) ? substr($data['start_time'], 0, 5) : '00:00';

        return trim($typeName . ' ' . $date . ' ' . $time);
    }
	private function routePrefix(): string
{
    return ActiveRole::routePrefix();
}
}