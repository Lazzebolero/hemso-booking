<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\GuideShift;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Services\LogService;
use Illuminate\Http\Request;

class TourController extends Controller
{
    public function index(Request $request)
    {
        $scope = $request->get('scope', 'upcoming');

        $query = Tour::with(['guide', 'tourType']);

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

        return view('admin.tours.index', compact('tours', 'scope'));
    }

    public function create()
    {
        $guides = User::where('role', 'guide')->orderBy('name')->get();

        $tour = new Tour();
        $tour->max_participants = (int) setting('default_tour_capacity', 25);
        $tour->status = 'planned';
        $tour->tour_type_id = TourType::where('is_default', true)->value('id');

        return view('admin.tours.create', compact('tour', 'guides'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if (blank($data['title'] ?? null) && (bool) setting('auto_generate_tour_title', 1)) {
            $data['title'] = $this->generateTourTitle($data);
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

        return redirect()->route('admin.tours.index')->with('success', 'Tur skapad.');
    }

    public function show(Tour $tour)
    {
        $tour->load(['guide', 'tourType', 'bookings']);

        $bookingCount = $tour->bookings()
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $bookedCount = $tour->bookings()
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_count');

        $availableSpots = max(0, $tour->max_participants - $bookedCount);

        $occupancyPercent = $tour->max_participants > 0
            ? round(($bookedCount / $tour->max_participants) * 100)
            : 0;

        $lifecycleLogs = ActivityLog::with('user')
            ->where('entity_type', 'tour')
            ->where('entity_id', $tour->id)
            ->whereIn('action', ['started', 'completed'])
            ->orderBy('created_at', 'desc')
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
                ->route('admin.tours.show', $tour)
                ->withErrors(['tour' => 'En avslutad tur kan inte redigeras.']);
        }

        $guides = User::where('role', 'guide')->orderBy('name')->get();

        return view('admin.tours.edit', compact('tour', 'guides'));
    }

    public function update(Request $request, Tour $tour)
    {
        if ($tour->status === 'completed') {
            return redirect()
                ->route('admin.tours.show', $tour)
                ->withErrors(['tour' => 'En avslutad tur kan inte ändras.']);
        }

        $old = $tour->toArray();
        $data = $this->validated($request);

        if (blank($data['title'] ?? null) && (bool) setting('auto_generate_tour_title', 1)) {
            $data['title'] = $this->generateTourTitle($data);
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

        return redirect()->route('admin.tours.index')->with('success', 'Tur uppdaterad.');
    }

    public function destroy(Tour $tour)
    {
        $old = $tour->toArray();

        $tour->delete();

        LogService::log(
            'tour',
            $tour->id,
            'deleted',
            $old,
            null,
            'Tog bort tur'
        );

        return redirect()->route('admin.tours.index')->with('success', 'Tur borttagen.');
    }

    public function start(Tour $tour)
    {
        if ($tour->status === 'completed') {
            return back()->withErrors([
                'tour' => 'En avslutad tur kan inte startas igen.'
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
                'tour' => 'Turen är redan avslutad.'
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
            'title' => 'nullable|string|max:255',
            'tour_type_id' => 'nullable|exists:tour_types,id',
            'description' => 'nullable|string',
            'tour_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'nullable',
            'max_participants' => 'required|integer|min:1',
            'guide_id' => 'nullable|exists:users,id',
            'status' => 'required|in:planned,started,completed,cancelled',
        ]);
    }

    private function generateTourTitle(array $data): string
    {
        $typeName = 'Tur';

        if (!empty($data['tour_type_id'])) {
            $type = TourType::find($data['tour_type_id']);
            if ($type) {
                $typeName = $type->name;
            }
        }

        $date = !empty($data['tour_date'])
            ? date('Y-m-d', strtotime($data['tour_date']))
            : now()->toDateString();

        $time = $data['start_time'] ?? '00:00';

        return trim($typeName . ' ' . $date . ' ' . $time);
    }

    private function syncShift(Tour $tour): void
    {
        if (!$tour->guide_id) {
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
}