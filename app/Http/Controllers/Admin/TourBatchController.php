<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Services\LogService;
use App\Support\ActiveRole;
use App\Support\Roles;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TourBatchController extends Controller
{
    public function create()
    {
        $guides = User::query()
            ->whereHas('roles', function ($query) {
                $query->where('slug', Roles::GUIDE);
            })
            ->orderBy('name')
            ->get();

        $tourTypes = TourType::where('is_active', true)
            ->orderBy('name')
            ->get();

        $defaultTourTypeId = TourType::where('is_default', true)->value('id');

        return view('admin.tours.batch-create', compact(
            'guides',
            'tourTypes',
            'defaultTourTypeId'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tour_date' => ['required', 'date'],
            'first_tour' => ['required', 'date_format:H:i'],
            'last_tour' => ['required', 'date_format:H:i'],
            'interval' => ['required', 'in:60,30,15'],
            'tour_type_id' => ['nullable', 'exists:tour_types,id'],
            'guide_id' => ['nullable', 'exists:users,id'],
            'max_participants' => ['required', 'integer', 'min:1'],
            'skip_existing' => ['nullable', 'boolean'],
        ]);

        $tourDate = Carbon::parse($data['tour_date'])->toDateString();
        $start = Carbon::parse($tourDate . ' ' . $data['first_tour']);
        $end = Carbon::parse($tourDate . ' ' . $data['last_tour']);
        $interval = (int) $data['interval'];

        if ($start->gt($end)) {
            return back()->withErrors([
                'last_tour' => 'Sista turen måste vara samma tid eller senare än första turen.',
            ])->withInput();
        }

        $tourType = null;
        $tourTypeId = $data['tour_type_id'] ?? null;

        if ($tourTypeId) {
            $tourType = TourType::find($tourTypeId);
        } else {
            $defaultTourTypeId = TourType::where('is_default', true)->value('id');
            if ($defaultTourTypeId) {
                $tourTypeId = $defaultTourTypeId;
                $tourType = TourType::find($defaultTourTypeId);
            }
        }

        $created = 0;
        $skipped = 0;

        while ($start->lte($end)) {
            $startTime = $start->format('H:i:s');

            $exists = Tour::whereDate('tour_date', $tourDate)
                ->where('start_time', $startTime)
                ->exists();

            if ($exists && $request->boolean('skip_existing')) {
                $skipped++;
                $start->addMinutes($interval);
                continue;
            }

            if (!$exists) {
                $title = $tourType
                    ? trim($tourType->name . ' ' . $tourDate . ' ' . $start->format('H:i'))
                    : 'Tur ' . $tourDate . ' ' . $start->format('H:i');

                $tour = Tour::create([
                    'title' => $title,
                    'tour_date' => $tourDate,
                    'start_time' => $startTime,
                    'end_time' => $this->resolveEndTime(
                        $startTime,
                        null,
                        $tourType
                    ),
                    'status' => 'planned',
                    'max_participants' => (int) $data['max_participants'],
                    'guide_id' => $data['guide_id'] ?? null,
                    'tour_type_id' => $tourTypeId,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                LogService::log(
                    'tour',
                    $tour->id,
                    'created',
                    null,
                    $tour->toArray(),
                    'Skapade tur via batch'
                );

                $created++;
            } else {
                $skipped++;
            }

            $start->addMinutes($interval);
        }

        return redirect()
            ->route($this->routePrefix() . '.tours.batch-create')
            ->with('success', "Klart. {$created} turer skapades, {$skipped} hoppades över.");
    }

    private function resolveEndTime(?string $startTime, ?string $endTime = null, ?TourType $tourType = null): ?string
    {
        if (!$startTime) {
            return $endTime;
        }

        if (!empty($endTime)) {
            return $this->normalizeTimeString($endTime);
        }

        $duration = 80;

        if ($tourType && !empty($tourType->default_duration_minutes)) {
            $duration = (int) $tourType->default_duration_minutes;
        }

        return Carbon::createFromFormat('H:i:s', $this->normalizeTimeString($startTime))
            ->addMinutes($duration)
            ->format('H:i:s');
    }

    private function normalizeTimeString(?string $time): ?string
    {
        if (empty($time)) {
            return null;
        }

        $time = trim($time);

        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }

        return $time;
    }

    private function routePrefix(): string
    {
        return ActiveRole::routePrefix();
    }
}