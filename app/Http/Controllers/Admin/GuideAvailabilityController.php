<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuideAvailabilityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'ignore_tour_id' => ['nullable', 'integer'],
        ]);

        $date = $data['date'];
        $startTime = $data['start_time'] ?? null;
        $endTime = $data['end_time'] ?? null;
        $ignoreTourId = $data['ignore_tour_id'] ?? null;

        $guides = User::query()
            ->whereHas('roles', function ($query) {
                $query->where('slug', Roles::GUIDE);
            })
            ->with(['workShifts' => function ($query) use ($date) {
                $query->whereDate('shift_date', $date)
                    ->where('shift_role', Roles::GUIDE)
                    ->whereNotIn('status', ['cancelled'])
                    ->orderBy('start_time');
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($guide) use ($date, $startTime, $endTime, $ignoreTourId) {
                $shift = $guide->workShifts->first();

                $shiftStart = $shift?->start_time
                    ? substr($shift->start_time, 0, 5)
                    : null;

                $tourConflict = $this->findTourConflict(
                    guideId: $guide->id,
                    date: $date,
                    startTime: $startTime,
                    endTime: $endTime,
                    ignoreTourId: $ignoreTourId
                );

                return [
                    'id' => $guide->id,
                    'name' => $guide->name,
                    'has_shift' => (bool) $shift,
                    'shift_start' => $shiftStart,
                    'has_conflict' => (bool) $tourConflict,
                    'conflict_text' => $tourConflict,
                    'label' => $this->buildLabel(
                        name: $guide->name,
                        shiftStart: $shiftStart,
                        hasShift: (bool) $shift,
                        hasConflict: (bool) $tourConflict
                    ),
                ];
            })
            ->values();

        return response()->json($guides);
    }

    private function buildLabel(string $name, ?string $shiftStart, bool $hasShift, bool $hasConflict): string
    {
        $parts = [$name];

        if ($shiftStart) {
            $parts[] = '[' . $shiftStart . ']';
        } elseif (! $hasShift) {
            $parts[] = '[Inget pass]';
        }

        if ($hasConflict) {
            $parts[] = '[Turkrock]';
        }

        return implode(' ', $parts);
    }

    private function findTourConflict(
        int $guideId,
        string $date,
        ?string $startTime,
        ?string $endTime,
        ?int $ignoreTourId = null
    ): ?string {
        if (! $startTime) {
            return null;
        }

        $effectiveEndTime = $endTime ?: '23:59';

        $query = Tour::query()
            ->where('guide_id', $guideId)
            ->whereDate('tour_date', $date)
            ->whereNotIn('status', ['cancelled'])
            ->where(function ($query) use ($startTime, $effectiveEndTime) {
                $query->where(function ($q) use ($startTime, $effectiveEndTime) {
                    $q->where('start_time', '<', $effectiveEndTime)
                      ->whereRaw('COALESCE(end_time, start_time) > ?', [$startTime]);
                });
            })
            ->orderBy('start_time');

        if ($ignoreTourId) {
            $query->where('id', '!=', $ignoreTourId);
        }

        $conflict = $query->first();

        if (! $conflict) {
            return null;
        }

        $title = $conflict->title ?: 'Tur';
        $tourStart = $conflict->start_time ? substr($conflict->start_time, 0, 5) : '--:--';

        return $title . ' kl ' . $tourStart;
    }
}