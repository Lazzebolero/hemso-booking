<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Language;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Services\LogService;
use App\Support\Roles;
use Illuminate\Http\Request;

class QuickTourController extends Controller
{
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
            return view('guide.quick-tours.create', compact(
                'languages',
                'defaultLanguageIds'
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
        'men_count' => ['nullable', 'integer', 'min:0'],
        'women_count' => ['nullable', 'integer', 'min:0'],
        'youth_count' => ['nullable', 'integer', 'min:0'],
        'child_count' => ['nullable', 'integer', 'min:0'],
        'notes' => ['nullable', 'string'],
        'guide_id' => ['nullable', 'exists:users,id'],
        'language_ids' => ['nullable', 'array'],
        'language_ids.*' => ['integer', 'exists:languages,id'],
    ], [
        'guide_id.exists' => 'Vald guide finns inte.',
        'language_ids.*.exists' => 'Ett valt språk finns inte.',
    ]);

    $data['men_count'] = (int) ($data['men_count'] ?? 0);
    $data['women_count'] = (int) ($data['women_count'] ?? 0);
    $data['youth_count'] = (int) ($data['youth_count'] ?? 0);
    $data['child_count'] = (int) ($data['child_count'] ?? 0);

    $totalCount = (int) $data['men_count']
        + (int) $data['women_count']
        + (int) $data['youth_count']
        + (int) $data['child_count'];

        $guideId = $this->resolveGuideId($data);
        $now = now();

        $tourType = TourType::query()
            ->where('is_default', true)
            ->first();

        if (! $tourType) {
            $tourType = TourType::query()->orderBy('id')->first();
        }

        $durationMinutes = (int) ($tourType->default_duration_minutes ?? 60);
        if ($durationMinutes <= 0) {
            $durationMinutes = 60;
        }

        $endAt = $now->copy()->addMinutes($durationMinutes);

        $tourTitle = 'Snabbtur ' . $now->format('Y-m-d H:i');
        $bookingName = 'Snabbtur ' . $now->format('Y-m-d H:i');

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
            'end_time' => $endAt->format('H:i:s'),
            'status' => 'started',
            'started_at' => $now,
            'tour_type_id' => $tourType?->id,
            'guide_id' => $guideId,
            'max_participants' => max(25, $totalCount),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $booking = Booking::create([
            'tour_id' => $tour->id,
            'booking_name' => $bookingName,
            'contact_name' => $bookingName,
            'men_count' => (int) $data['men_count'],
            'women_count' => (int) $data['women_count'],
            'youth_count' => (int) $data['youth_count'],
            'child_count' => (int) $data['child_count'],
            'total_count' => $totalCount,
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

        return !empty($data['guide_id']) ? (int) $data['guide_id'] : null;
    }
}