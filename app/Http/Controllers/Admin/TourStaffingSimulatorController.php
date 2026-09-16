<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TourStaffingSimulatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class TourStaffingSimulatorController extends Controller
{
    public function __construct(
        private TourStaffingSimulatorService $simulator,
    ) {}

    public function index(Request $request): View
    {
        $request->merge([
            'guide_start' => $this->normalizeRequestTime($request->input('guide_start')),
            'guide_reduce_at' => $this->normalizeRequestTime($request->input('guide_reduce_at')),
            'first_tour' => $this->normalizeRequestTime($request->input('first_tour')),
            'last_tour' => $this->normalizeRequestTime($request->input('last_tour')),
        ]);

        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'guide_count' => ['nullable', 'integer', 'min:1', 'max:8'],
            'guide_start' => ['nullable', 'date_format:H:i'],
            'guide_starts' => ['nullable', 'string', 'max:200'],
            'guide_reduce_at' => ['nullable', 'date_format:H:i'],
            'guide_count_after' => ['nullable', 'integer', 'min:0', 'max:8'],
            'first_tour' => ['nullable', 'date_format:H:i'],
            'last_tour' => ['nullable', 'date_format:H:i'],
            'ferry_adjustment' => ['nullable', 'integer', 'in:-10,0,10'],
            'schedule_mode' => ['nullable', 'string', 'in:classic,dynamic'],
            'interval' => ['nullable', 'integer', 'in:15,30,60'],
            'max_wait_minutes' => ['nullable', 'integer', 'min:0', 'max:180'],
            'duration_minutes' => ['nullable', 'integer', 'min:45', 'max:120'],
            'buffer_minutes' => ['nullable', 'integer', 'min:0', 'max:60'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $date = Carbon::parse($validated['date'] ?? now()->subDay()->toDateString())->startOfDay();

        $guideStarts = [];
        if (! empty($validated['guide_starts'])) {
            foreach (preg_split('/[\s,;]+/', (string) $validated['guide_starts']) as $part) {
                $normalized = $this->normalizeRequestTime($part);
                if ($normalized !== null) {
                    $guideStarts[] = $normalized;
                }
            }
        }

        $params = [
            'guide_count' => (int) ($validated['guide_count'] ?? 2),
            'guide_start' => $validated['guide_start'] ?? '11:00',
            'guide_starts' => $guideStarts,
            'guide_reduce_at' => $validated['guide_reduce_at'] ?? null,
            'guide_count_after' => isset($validated['guide_count_after'])
                ? (int) $validated['guide_count_after']
                : null,
            'first_tour' => $validated['first_tour'] ?? '11:00',
            'last_tour' => $validated['last_tour'] ?? '16:00',
            'ferry_adjustment' => (int) ($validated['ferry_adjustment'] ?? 0),
            'schedule_mode' => (string) ($validated['schedule_mode'] ?? 'classic'),
            'interval' => (int) ($validated['interval'] ?? 60),
            'max_wait_minutes' => (int) ($validated['max_wait_minutes'] ?? 45),
            'duration_minutes' => (int) ($validated['duration_minutes'] ?? 75),
            'buffer_minutes' => (int) ($validated['buffer_minutes'] ?? 20),
            'capacity' => (int) ($validated['capacity'] ?? 27),
        ];

        $result = $this->simulator->simulate($date, $params);

        return view('admin.statistics.tour-staffing-simulator', [
            'date' => $date,
            'form' => [
                'date' => $date->toDateString(),
                'guide_count' => $result['params']['guide_count'],
                'guide_start' => $params['guide_start'],
                'guide_starts' => implode(', ', $result['params']['guide_starts']),
                'guide_reduce_at' => $result['params']['guide_reduce_at'] ?? '',
                'guide_count_after' => $result['params']['guide_count_after'],
                'first_tour' => $result['params']['first_tour'],
                'last_tour' => $result['params']['last_tour'],
                'ferry_adjustment' => $result['params']['ferry_adjustment'],
                'schedule_mode' => $result['params']['schedule_mode'],
                'interval' => $result['params']['interval'],
                'max_wait_minutes' => $result['params']['max_wait_minutes'],
                'duration_minutes' => $result['params']['duration_minutes'],
                'buffer_minutes' => $result['params']['buffer_minutes'],
                'capacity' => $result['params']['capacity'],
            ],
            'result' => $result,
        ]);
    }

    private function normalizeRequestTime(mixed $value): ?string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $raw, $matches) !== 1) {
            return $raw;
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return $raw;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }
}
