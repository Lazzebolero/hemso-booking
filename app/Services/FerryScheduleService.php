<?php

namespace App\Services;

use App\Models\FerryDeparture;
use App\Models\Setting;
use App\Models\Tour;
use App\Models\User;
use App\Models\WorkShift;
use App\Services\Trafikverket\FerryTrafficService;
use App\Support\FerryDayTypes;
use App\Support\FerryDirections;
use App\Support\FerryStrinningenTimetable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FerryScheduleService
{
    public function __construct(
        private FerryTrafficService $ferryTraffic,
    ) {}

    public function adjustmentMarginMinutes(): int
    {
        $value = Setting::query()
            ->where('key', 'ferry_adjustment_margin_minutes')
            ->value('value');

        $minutes = is_numeric($value) ? (int) $value : 30;

        return max(1, $minutes);
    }

    public function dayTypeFor(CarbonInterface $date): string
    {
        return FerryDayTypes::forDate($date);
    }

    public function dayTypeLabelFor(CarbonInterface $date): string
    {
        return FerryDayTypes::labelForDate($date);
    }

    /**
     * @return Collection<int, FerryDeparture>
     */
    public function departuresForDate(CarbonInterface $date, string $direction): Collection
    {
        $dayType = $this->dayTypeFor($date);
        $trafficMap = $this->ferryTraffic->statusMapForDate($date, $direction);

        if ($direction === FerryDirections::TO_MAINLAND && $trafficMap !== []) {
            return collect($trafficMap)
                ->keys()
                ->sort()
                ->values()
                ->map(function (string $time, int $index) use ($dayType, $direction, $trafficMap): FerryDeparture {
                    $live = $trafficMap[$time] ?? [];

                    return new FerryDeparture([
                        'direction' => $direction,
                        'day_type' => $dayType,
                        'departure_time' => $time.':00',
                        'requires_call' => false,
                        'sort_order' => $index,
                    ]);
                });
        }

        if ($direction !== FerryDirections::TO_ISLAND) {
            return collect();
        }

        return collect(FerryStrinningenTimetable::departuresFor($dayType))
            ->values()
            ->map(function (array $departure, int $index) use ($dayType, $direction): FerryDeparture {
                $model = new FerryDeparture([
                    'direction' => $direction,
                    'day_type' => $dayType,
                    'departure_time' => $departure['time'].':00',
                    'requires_call' => $departure['requires_call'],
                    'sort_order' => $index,
                ]);
                $model->no_duplicates = $departure['no_duplicates'] ?? false;

                return $model;
            });
    }

    /**
     * @return array{
     *     date: string,
     *     direction: string,
     *     day_type: string,
     *     day_type_label: string,
     *     departures: list<array{
     *         time: string,
     *         requires_call: bool,
     *         no_duplicates: bool,
     *         status: string,
     *         status_label: string,
     *         minutes_until: ?int,
     *         is_cancelled: bool,
     *         delay_minutes: ?int,
     *         live_time: ?string,
     *         traffic_message: ?string
     *     }>,
     *     last: ?array{
     *         time: string,
     *         status_label: string,
     *         live_time: ?string,
     *         is_extra: bool,
     *         from_live_api: bool,
     *         minutes_ago: ?int,
     *         guest_arrival_minutes_min: ?int,
     *         guest_arrival_minutes_max: ?int
     *     },
     *     next: ?array{time: string, status_label: string, minutes_until: ?int, requires_call: bool, no_duplicates: bool, live_time: ?string, is_cancelled: bool, delay_minutes: ?int},
     *     traffic_alerts: list<array{level: string, title: string, message: string}>,
     *     traffic_fetched_at: ?string,
     *     traffic_live: bool
     * }
     */
    public function daySnapshot(CarbonInterface $date, string $direction, ?CarbonInterface $at = null): array
    {
        $at ??= now();
        $dayType = $this->dayTypeFor($date);
        $departures = $this->departuresForDate($date, $direction);
        $trafficMap = $this->ferryTraffic->statusMapForDate($date, $direction);
        $trafficAlerts = $this->ferryTraffic->alertsForDate($date, $direction);
        $cachedDay = $this->ferryTraffic->cachedDay($date);

        $nextIndex = null;
        $mapped = $departures->values()->map(function (FerryDeparture $departure, int $index) use ($date, $at, $trafficMap, &$nextIndex) {
            $scheduledAt = $this->scheduledAt($date, (string) $departure->departure_time);
            $timeLabel = $scheduledAt->format('H:i');
            $live = $trafficMap[$timeLabel] ?? null;
            $isCancelled = (bool) ($live['is_cancelled'] ?? false);
            $delayMinutes = $live['delay_minutes'] ?? null;
            $liveTime = $live['departure_time'] ?? null;
            $displayAt = $liveTime !== null && $delayMinutes !== null
                ? $this->scheduledAt($date, $liveTime)
                : $scheduledAt;
            $minutesUntil = (int) $at->diffInMinutes($displayAt, false);

            if ($isCancelled) {
                $status = 'cancelled';
                $statusLabel = 'Inställd';
            } elseif ($displayAt->lte($at)) {
                $status = 'passed';
                $statusLabel = 'Genomförd';
            } elseif ($nextIndex === null && ! $isCancelled) {
                $nextIndex = $index;
                $status = 'next';
                $statusLabel = $delayMinutes !== null && $delayMinutes > 0
                    ? 'Försenad'
                    : ($minutesUntil === 0 ? 'Nu' : 'Nästa');
            } else {
                $status = 'planned';
                $statusLabel = $delayMinutes !== null && $delayMinutes > 0 ? 'Försenad' : 'Planerad';
            }

            return [
                'time' => $timeLabel,
                'live_time' => $liveTime,
                'requires_call' => (bool) $departure->requires_call,
                'no_duplicates' => (bool) ($departure->no_duplicates ?? false),
                'status' => $status,
                'status_label' => $statusLabel,
                'minutes_until' => $minutesUntil > 0 ? $minutesUntil : null,
                'is_cancelled' => $isCancelled,
                'delay_minutes' => $delayMinutes,
                'traffic_message' => $live['message'] ?? null,
            ];
        })->all();

        $passed = collect($mapped)->filter(fn (array $item) => $item['status'] === 'passed');
        $last = $passed->last();
        $next = collect($mapped)->first(fn (array $item) => in_array($item['status'], ['next', 'planned'], true) && ! $item['is_cancelled']);

        $last = $this->mergeLiveLastDeparture($date, $direction, $at, $last);

        return [
            'date' => $date->toDateString(),
            'direction' => $direction,
            'day_type' => $dayType,
            'day_type_label' => $this->dayTypeLabelFor($date),
            'departures' => $mapped,
            'last' => $last ? [
                'time' => $last['live_time'] ?? $last['time'],
                'status_label' => $last['status_label'] ?? 'Avgått',
                'live_time' => $last['live_time'] ?? null,
                'is_extra' => (bool) ($last['is_extra'] ?? false),
                'from_live_api' => (bool) ($last['from_live_api'] ?? false),
                'minutes_ago' => $last['minutes_ago'] ?? null,
                'guest_arrival_minutes_min' => $last['guest_arrival_minutes_min'] ?? null,
                'guest_arrival_minutes_max' => $last['guest_arrival_minutes_max'] ?? null,
                'guest_arrival_relevant' => (bool) ($last['guest_arrival_relevant'] ?? false),
            ] : null,
            'next' => $next ? [
                'time' => $next['live_time'] ?? $next['time'],
                'scheduled_time' => $next['time'],
                'status_label' => $next['status_label'],
                'minutes_until' => $next['minutes_until'],
                'requires_call' => $next['requires_call'],
                'no_duplicates' => $next['no_duplicates'],
                'live_time' => $next['live_time'],
                'is_cancelled' => $next['is_cancelled'],
                'delay_minutes' => $next['delay_minutes'],
            ] : null,
            'traffic_alerts' => $trafficAlerts,
            'traffic_fetched_at' => is_array($cachedDay) ? ($cachedDay['fetched_at'] ?? null) : null,
            'traffic_live' => $this->ferryTraffic->isEnabled() && is_array($cachedDay),
        ];
    }

    public function commuteHintForUser(?User $user, CarbonInterface $date, string $direction): ?string
    {
        if ($user === null) {
            return null;
        }

        $shift = WorkShift::query()
            ->where('user_id', $user->id)
            ->whereDate('shift_date', $date->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_time')
            ->first();

        if ($shift === null) {
            return null;
        }

        $snapshot = $this->daySnapshot($date, $direction);
        $next = $snapshot['next'];

        if ($direction === FerryDirections::TO_ISLAND && ! empty($shift->start_time)) {
            if ($next === null) {
                return 'Ingen fler färja till ön idag enligt tabellen.';
            }

            $ferryAt = $this->scheduledAt($date, $next['time']);
            $shiftStart = $this->scheduledAt($date, substr((string) $shift->start_time, 0, 5));

            if ($ferryAt->lte($shiftStart)) {
                return 'Du hinner med färjan '.$next['time'].' till passstart '.$shiftStart->format('H:i').'.';
            }

            return 'Nästa färja '.$next['time'].' är efter passstart '.$shiftStart->format('H:i').' – planera i tid.';
        }

        if ($direction === FerryDirections::TO_MAINLAND && ! empty($shift->end_time)) {
            if ($next === null) {
                return 'Ingen fler hemfärja idag enligt tabellen.';
            }

            $shiftEnd = $this->scheduledAt($date, substr((string) $shift->end_time, 0, 5));

            return 'Nästa hemfärja '.$next['time'].' efter pass slut '.$shiftEnd->format('H:i').'.';
        }

        return null;
    }

    /**
     * @return array{level: string, message: string, ferry_time: string}|null
     */
    public function tourFerryAdvice(Tour $tour, ?CarbonInterface $at = null): ?array
    {
        if (($tour->status ?? 'planned') !== 'planned' || empty($tour->start_time) || empty($tour->tour_date)) {
            return null;
        }

        $at ??= now();
        $tourDate = Carbon::parse($tour->tour_date);
        $tourStart = $this->scheduledAt($tourDate, substr((string) $tour->start_time, 0, 5));

        if ($tourStart->lt($at->copy()->startOfDay())) {
            return null;
        }

        $ferryTime = $this->latestDepartureBefore($tourDate, FerryDirections::TO_ISLAND, $tourStart);

        if ($ferryTime === null) {
            return null;
        }

        $ferryAt = $this->scheduledAt($tourDate, $ferryTime);
        $margin = $ferryAt->diffInMinutes($tourStart, false);
        $required = $this->adjustmentMarginMinutes();

        if ($margin >= $required) {
            return [
                'level' => 'ok',
                'message' => 'Ingen korrigering rekommenderas (≥ '.$required.' min efter färja '.$ferryTime.').',
                'ferry_time' => $ferryTime,
            ];
        }

        return [
            'level' => 'warn',
            'message' => 'Kort marginal till färja '.$ferryTime.' ('.$margin.' min) – kontrollera färjekorrigering.',
            'ferry_time' => $ferryTime,
        ];
    }

    /**
     * @return Collection<int, Tour>
     */
    public function upcomingToursNeedingAdvice(?CarbonInterface $at = null): Collection
    {
        $at ??= now();
        $today = $at->toDateString();

        return Tour::query()
            ->whereDate('tour_date', $today)
            ->where('status', 'planned')
            ->whereTime('start_time', '>=', $at->format('H:i:s'))
            ->orderBy('start_time')
            ->get()
            ->map(function (Tour $tour) use ($at) {
                $tour->ferry_advice = $this->tourFerryAdvice($tour, $at);

                return $tour;
            })
            ->filter(fn (Tour $tour) => ($tour->ferry_advice['level'] ?? null) === 'warn');
    }

    private function latestDepartureBefore(CarbonInterface $date, string $direction, CarbonInterface $before): ?string
    {
        $latest = null;

        foreach ($this->departuresForDate($date, $direction) as $departure) {
            $time = substr((string) $departure->departure_time, 0, 5);
            $scheduledAt = $this->scheduledAt($date, $time);

            if ($scheduledAt->lte($before)) {
                $latest = $time;
            }
        }

        return $latest;
    }

    private function scheduledAt(CarbonInterface $date, string $time): Carbon
    {
        $normalized = strlen($time) === 5 ? $time.':00' : $time;

        return Carbon::parse($date->toDateString().' '.$normalized);
    }

    /**
     * @param  ?array{time: string, status_label: string, live_time: ?string}  $timetableLast
     * @return ?array{
     *     time: string,
     *     status_label: string,
     *     live_time: ?string,
     *     is_extra: bool,
     *     from_live_api: bool,
     *     minutes_ago: ?int,
     *     guest_arrival_minutes_min: ?int,
     *     guest_arrival_minutes_max: ?int
     * }
     */
    private function mergeLiveLastDeparture(
        CarbonInterface $date,
        string $direction,
        CarbonInterface $at,
        ?array $timetableLast,
    ): ?array {
        $liveLast = $this->ferryTraffic->lastLiveDeparture($date, $direction, $at);

        if ($liveLast === null) {
            if ($timetableLast === null) {
                return null;
            }

            return [
                'time' => $timetableLast['time'],
                'status_label' => 'Avgått',
                'live_time' => $timetableLast['live_time'] ?? null,
                'is_extra' => false,
                'from_live_api' => false,
                'minutes_ago' => null,
                'guest_arrival_minutes_min' => null,
                'guest_arrival_minutes_max' => null,
            ];
        }

        $useLive = true;

        if ($timetableLast !== null) {
            $timetableAt = $this->scheduledAt($date, $timetableLast['time']);
            $liveAt = Carbon::parse($liveLast['departed_at']);
            $useLive = $liveAt->gte($timetableAt);
        }

        if (! $useLive && $timetableLast !== null) {
            return [
                'time' => $timetableLast['time'],
                'status_label' => 'Avgått',
                'live_time' => $timetableLast['live_time'] ?? null,
                'is_extra' => false,
                'from_live_api' => false,
                'minutes_ago' => null,
                'guest_arrival_minutes_min' => null,
                'guest_arrival_minutes_max' => null,
            ];
        }

        $extraAlertMinutes = (int) config('trafikverket.extra_departure_alert_minutes', 25);

        return [
            'time' => $liveLast['time'],
            'status_label' => ($liveLast['is_extra'] ?? false) ? 'Extratur avgått' : 'Avgått',
            'live_time' => $liveLast['time'],
            'is_extra' => (bool) ($liveLast['is_extra'] ?? false),
            'from_live_api' => true,
            'minutes_ago' => $liveLast['minutes_ago'],
            'guest_arrival_minutes_min' => $liveLast['guest_arrival_minutes_min'],
            'guest_arrival_minutes_max' => $liveLast['guest_arrival_minutes_max'],
            'guest_arrival_relevant' => ($liveLast['minutes_ago'] ?? 999) <= $extraAlertMinutes,
        ];
    }
}
