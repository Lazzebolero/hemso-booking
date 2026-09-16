<?php

namespace App\Services;

use App\Models\Booking;
use App\Support\FerryDayTypes;
use App\Support\FerryStrinningenTimetable;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class FerryBookingWaveService
{
    /** Ungefärlig restid från Strinningen-avgång till ankomst på plats. */
    public const TRAVEL_MINUTES = 20;

    /**
     * Kopplar bokningar (created_at) till närmast föregående färjeavgång från Strinningen,
     * med hänsyn till restid: färja ≤ created_at − 20 min.
     *
     * @return array{
     *     travel_minutes: int,
     *     summary: array{bookings: int, people: int, matched_bookings: int, matched_people: int},
     *     unmatched: array{bookings: int, people: int},
     *     waves: list<array{
     *         departure_time: string,
     *         expected_arrival: string,
     *         label: string,
     *         bookings: int,
     *         people: int
     *     }>
     * }
     */
    public function build(Carbon $from, Carbon $to, ?int $tourTypeId = null): array
    {
        $bookings = $this->queryBookings($from, $to, $tourTypeId);
        $departureCache = [];

        /** @var array<string, array{departure_time: string, bookings: int, people: int}> $waves */
        $waves = [];
        $unmatchedBookings = 0;
        $unmatchedPeople = 0;
        $matchedBookings = 0;
        $matchedPeople = 0;

        foreach ($bookings as $booking) {
            $createdAt = $booking->created_at;
            $people = (int) ($booking->total_count ?? 0);

            if ($createdAt === null) {
                $unmatchedBookings++;
                $unmatchedPeople += $people;

                continue;
            }

            $match = $this->matchDeparture($createdAt, $departureCache);

            if ($match === null) {
                $unmatchedBookings++;
                $unmatchedPeople += $people;

                continue;
            }

            $key = $match;
            if (! isset($waves[$key])) {
                $waves[$key] = [
                    'departure_time' => $key,
                    'bookings' => 0,
                    'people' => 0,
                ];
            }

            $waves[$key]['bookings']++;
            $waves[$key]['people'] += $people;
            $matchedBookings++;
            $matchedPeople += $people;
        }

        ksort($waves);

        $rows = [];
        foreach ($waves as $wave) {
            $departure = Carbon::createFromFormat('H:i', $wave['departure_time']);
            $arrival = $departure->copy()->addMinutes(self::TRAVEL_MINUTES);

            $rows[] = [
                'departure_time' => $wave['departure_time'],
                'expected_arrival' => $arrival->format('H:i'),
                'label' => 'Avgång '.$wave['departure_time'].' · ankomst ca '.$arrival->format('H:i'),
                'bookings' => $wave['bookings'],
                'people' => $wave['people'],
            ];
        }

        return [
            'travel_minutes' => self::TRAVEL_MINUTES,
            'summary' => [
                'bookings' => (int) $bookings->count(),
                'people' => (int) $bookings->sum(fn (Booking $booking) => (int) ($booking->total_count ?? 0)),
                'matched_bookings' => $matchedBookings,
                'matched_people' => $matchedPeople,
            ],
            'unmatched' => [
                'bookings' => $unmatchedBookings,
                'people' => $unmatchedPeople,
            ],
            'waves' => $rows,
        ];
    }

    /**
     * @param  array<string, list<string>>  $departureCache
     */
    public function matchDeparture(CarbonInterface $createdAt, array &$departureCache = []): ?string
    {
        $cutoff = $createdAt->copy()->subMinutes(self::TRAVEL_MINUTES);

        $datesToSearch = [
            $cutoff->copy()->subDay()->toDateString(),
            $cutoff->toDateString(),
        ];

        if ($createdAt->toDateString() !== $cutoff->toDateString()) {
            $datesToSearch[] = $createdAt->toDateString();
        }

        $datesToSearch = array_values(array_unique($datesToSearch));

        $latestAt = null;
        $latestTime = null;

        foreach ($datesToSearch as $dateKey) {
            foreach ($this->departureTimesForDate($dateKey, $departureCache) as $time) {
                $scheduledAt = Carbon::parse($dateKey.' '.$time.':00');

                if ($scheduledAt->lte($cutoff) && ($latestAt === null || $scheduledAt->gt($latestAt))) {
                    $latestAt = $scheduledAt;
                    $latestTime = $time;
                }
            }
        }

        return $latestTime;
    }

    /**
     * @param  array<string, list<string>>  $departureCache
     * @return list<string>
     */
    private function departureTimesForDate(string $dateKey, array &$departureCache): array
    {
        if (isset($departureCache[$dateKey])) {
            return $departureCache[$dateKey];
        }

        $date = Carbon::parse($dateKey);
        $dayType = FerryDayTypes::forDate($date);

        $times = collect(FerryStrinningenTimetable::departuresFor($dayType))
            ->map(fn (array $departure) => (string) $departure['time'])
            ->values()
            ->all();

        $departureCache[$dateKey] = $times;

        return $times;
    }

    /**
     * @return Collection<int, Booking>
     */
    private function queryBookings(Carbon $from, Carbon $to, ?int $tourTypeId): Collection
    {
        return Booking::query()
            ->where('is_waitlist', false)
            ->whereNotIn('status', ['cancelled'])
            ->where('created_at', '>=', $from->copy()->startOfDay())
            ->where('created_at', '<=', $to->copy()->endOfDay())
            ->when($tourTypeId !== null, function ($query) use ($tourTypeId) {
                $query->whereHas('tour', fn ($tourQuery) => $tourQuery->where('tour_type_id', $tourTypeId));
            })
            ->get(['id', 'total_count', 'created_at']);
    }
}
