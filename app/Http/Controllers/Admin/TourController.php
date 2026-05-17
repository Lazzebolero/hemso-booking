<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\GuideShift;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Services\LogService;
use App\Support\ActiveRole;
use App\Support\Roles;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TourController extends Controller
{
    public function index(Request $request)
    {
        $scope = $request->get('scope', 'upcoming');

        $query = Tour::with([
            'guide',
            'tourType',
            'bookings.languages',
        ]);

        if ($request->filled('q')) {
            $search = trim((string) $request->q);

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('guide', function ($guideQuery) use ($search) {
                        $guideQuery->where('name', 'like', "%{$search}%");
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

        return view('admin.tours.index', compact('tours', 'scope'));
    }

    public function create()
    {
        $tourTypes = TourType::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $defaultTourTypeId = TourType::where('is_default', true)->value('id');

        $tour = new Tour;
        $tour->tour_date = now()->toDateString();
        $tour->max_participants = (int) setting('default_tour_capacity', 25);
        $tour->status = 'planned';
        $tour->tour_type_id = $defaultTourTypeId;

        $guides = $this->guideUsersForDate($tour->tour_date);

        return view('admin.tours.create', [
            'tour' => $tour,
            'guides' => $guides,
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
            isset($data['tour_type_id']) ? (int) $data['tour_type_id'] : null
        );

        if (blank($data['title'] ?? null) && (bool) setting('auto_generate_tour_title', 1)) {
            $data['title'] = $this->generateTourTitle($data);
        }

        if (($data['status'] ?? null) === 'started') {
            $data['started_at'] = now();
        }

        if (($data['status'] ?? null) === 'completed') {
            $data['started_at'] = now();
            $data['ended_at'] = now();
        }

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $tour = Tour::create($data);

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
            'guide',
            'tourType',
            'bookings.languages',
        ]);

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

        return view('admin.tours.show', compact(
            'tour',
            'bookingCount',
            'bookedCount',
            'availableSpots',
            'occupancyPercent',
            'startedLog',
            'completedLog'
        ));
    }

    public function edit(Tour $tour)
    {
        if ($tour->status === 'completed') {
            return redirect()
                ->route($this->routePrefix().'.tours.show', $tour)
                ->withErrors(['tour' => 'En avslutad tur kan inte redigeras.']);
        }

        $tourTypes = TourType::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $defaultTourTypeId = TourType::where('is_default', true)->value('id');

        $guides = $this->guideUsersForDate($tour->tour_date);

        return view('admin.tours.edit', [
            'tour' => $tour,
            'guides' => $guides,
            'tourTypes' => $tourTypes,
            'defaultTourTypeId' => $defaultTourTypeId,
        ]);
    }

    public function update(Request $request, Tour $tour)
    {
        if ($tour->status === 'completed') {
            return redirect()
                ->route($this->routePrefix().'.tours.show', $tour)
                ->withErrors(['tour' => 'En avslutad tur kan inte ändras.']);
        }

        $old = $tour->toArray();
        $oldStatus = $tour->status;

        $data = $this->validated($request);

        $data['end_time'] = $this->resolveEndTime(
            $data['start_time'] ?? null,
            $data['end_time'] ?? null,
            isset($data['tour_type_id']) ? (int) $data['tour_type_id'] : null
        );

        if (blank($data['title'] ?? null) && (bool) setting('auto_generate_tour_title', 1)) {
            $data['title'] = $this->generateTourTitle($data);
        }

        if (($data['status'] ?? null) === 'started' && empty($tour->started_at)) {
            $data['started_at'] = now();
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

        $this->syncShift($tour);

        LogService::log(
            'tour',
            $tour->id,
            'updated',
            $old,
            $tour->fresh()->toArray(),
            'Uppdaterade tur'
        );

        return redirect()
            ->route($this->routePrefix().'.tours.index')
            ->with('success', 'Tur uppdaterad.');
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

    public function destroy(Tour $tour)
    {
        $old = $tour->toArray();

        GuideShift::where('tour_id', $tour->id)->delete();

        $tour->delete();

        LogService::log(
            'tour',
            $tour->id,
            'deleted',
            $old,
            null,
            'Tog bort tur'
        );

        return redirect()
            ->route($this->routePrefix().'.tours.index')
            ->with('success', 'Tur borttagen.');
    }

    public function start(Tour $tour)
    {
        if ($tour->status === 'completed') {
            return back()->withErrors([
                'tour' => 'En avslutad tur kan inte startas igen.',
            ]);
        }

        $tour->update([
            'status' => 'started',
            'started_at' => now(),
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
            'Startade tur'
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

    private function resolveEndTime(?string $startTime, ?string $endTime = null, ?int $tourTypeId = null): ?string
    {
        if (! $startTime) {
            return $endTime;
        }

        if (! empty($endTime)) {
            return $endTime;
        }

        $duration = $this->resolveDurationMinutes($tourTypeId);

        return Carbon::createFromFormat('H:i', substr($startTime, 0, 5))
            ->addMinutes($duration)
            ->format('H:i');
    }

    private function resolveDurationMinutes(?int $tourTypeId = null): int
    {
        if ($tourTypeId) {
            $duration = TourType::where('id', $tourTypeId)->value('default_duration_minutes');

            if ($duration) {
                return (int) $duration;
            }
        }

        return 80;
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
