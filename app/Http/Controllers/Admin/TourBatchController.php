<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\TourType;
use App\Services\DailyGuideOrderService;
use App\Services\LogService;
use App\Services\TourDurationSettingsService;
use App\Support\ActiveRole;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TourBatchController extends Controller
{
    public function __construct(
        private DailyGuideOrderService $dailyGuideOrderService
    ) {}

    public function create(Request $request)
    {
        $tourDate = $request->filled('tour_date')
            ? Carbon::parse($request->string('tour_date'))->toDateString()
            : now()->toDateString();

        $this->dailyGuideOrderService->ensureInitialized($tourDate, auth()->id());

        $tourTypes = TourType::activeOrdered();
        $defaultTourTypeId = TourType::where('is_default', true)->value('id');
        $dailyGuideOrders = $this->dailyGuideOrderService->orderedForDate($tourDate);

        return view('admin.tours.batch-create', [
            'tourTypes' => $tourTypes,
            'defaultTourTypeId' => $defaultTourTypeId,
            'dailyGuideOrders' => $dailyGuideOrders,
            'selectedTourDate' => $tourDate,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tour_date' => ['required', 'date'],
            'first_tour' => ['required', 'date_format:H:i'],
            'last_tour' => ['required', 'date_format:H:i'],
            'interval' => ['required', 'in:60,30,15'],
            'tour_type_id' => ['nullable', 'exists:tour_types,id'],
            'max_participants' => ['required', 'integer', 'min:1'],
            'skip_existing' => ['nullable', 'boolean'],
            'assign_daily_guides' => ['nullable', 'boolean'],
        ]);

        $assignDailyGuides = $request->boolean('assign_daily_guides');
        $tourDate = Carbon::parse($data['tour_date'])->toDateString();
        $start = Carbon::parse($tourDate.' '.$data['first_tour']);
        $end = Carbon::parse($tourDate.' '.$data['last_tour']);
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

        $guideIds = $assignDailyGuides
            ? $this->dailyGuideOrderService->guideIdsForDate($tourDate, auth()->id())
            : [];

        $rotationIndex = $this->dailyGuideOrderService->rotationStartIndexForDate($tourDate);

        $created = 0;
        $skipped = 0;
        $assignedGuideCount = 0;

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

            if (! $exists) {
                $guideId = $assignDailyGuides
                    ? $this->dailyGuideOrderService->guideIdAtRotationIndex($guideIds, $rotationIndex)
                    : null;

                if ($guideId !== null) {
                    $assignedGuideCount++;
                }

                $title = $tourType
                    ? trim($tourType->name.' '.$tourDate.' '.$start->format('H:i'))
                    : 'Tur '.$tourDate.' '.$start->format('H:i');

                $tour = Tour::create([
                    'title' => $title,
                    'tour_date' => $tourDate,
                    'start_time' => $startTime,
                    'end_time' => $this->resolveEndTime(
                        $startTime,
                        null,
                        $tourType,
                        $tourDate,
                    ),
                    'status' => 'planned',
                    'max_participants' => (int) $data['max_participants'],
                    'guide_id' => $guideId,
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
                $rotationIndex++;
            } else {
                $skipped++;
            }

            $start->addMinutes($interval);
        }

        $message = "Klart. {$created} turer skapades, {$skipped} hoppades över.";

        if ($assignDailyGuides && $created > 0) {
            if ($guideIds === []) {
                $message .= ' Inga guider fanns i dagens lista — turer skapades utan guide.';
            } else {
                $message .= " Guider tilldelades enligt dagens ordning ({$assignedGuideCount} turer).";
            }
        }

        return redirect()
            ->route($this->routePrefix().'.tours.batch-create', ['tour_date' => $tourDate])
            ->with('success', $message);
    }

    private function resolveEndTime(?string $startTime, ?string $endTime = null, ?TourType $tourType = null, ?string $tourDate = null): ?string
    {
        if (! $startTime) {
            return $endTime;
        }

        if (! empty($endTime)) {
            return $this->normalizeTimeString($endTime);
        }

        return app(TourDurationSettingsService::class)
            ->endTimeFromStartTime($this->normalizeTimeString($startTime), $tourType?->id);
    }

    private function normalizeTimeString(?string $time): ?string
    {
        if (empty($time)) {
            return null;
        }

        $time = trim($time);

        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time.':00';
        }

        return $time;
    }

    private function routePrefix(): string
    {
        return ActiveRole::routePrefix();
    }
}
