<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BookingInflowService
{
    /**
     * Bokningsinflöde baserat på created_at: timmar med expanderbara 5-minutersintervall.
     *
     * @return array{
     *     summary: array{bookings: int, people: int},
     *     hours: list<array{
     *         hour: int,
     *         label: string,
     *         bookings: int,
     *         people: int,
     *         slots: list<array{minute: int, label: string, bookings: int, people: int}>
     *     }>
     * }
     */
    public function build(Carbon $from, Carbon $to, ?int $tourTypeId = null): array
    {
        $bookings = $this->queryBookings($from, $to, $tourTypeId);

        /** @var array<int, array{hour: int, bookings: int, people: int, slots: array<int, array{minute: int, bookings: int, people: int}>}> $hours */
        $hours = [];

        foreach ($bookings as $booking) {
            $createdAt = $booking->created_at;

            if ($createdAt === null) {
                continue;
            }

            $hour = (int) $createdAt->format('G');
            $minute = intdiv((int) $createdAt->format('i'), 5) * 5;
            $people = (int) ($booking->total_count ?? 0);

            if (! isset($hours[$hour])) {
                $hours[$hour] = [
                    'hour' => $hour,
                    'bookings' => 0,
                    'people' => 0,
                    'slots' => [],
                ];
            }

            $hours[$hour]['bookings']++;
            $hours[$hour]['people'] += $people;

            if (! isset($hours[$hour]['slots'][$minute])) {
                $hours[$hour]['slots'][$minute] = [
                    'minute' => $minute,
                    'bookings' => 0,
                    'people' => 0,
                ];
            }

            $hours[$hour]['slots'][$minute]['bookings']++;
            $hours[$hour]['slots'][$minute]['people'] += $people;
        }

        ksort($hours);

        $rows = [];

        foreach ($hours as $hourRow) {
            ksort($hourRow['slots']);

            $slots = [];
            foreach ($hourRow['slots'] as $slot) {
                $slots[] = [
                    'minute' => $slot['minute'],
                    'label' => $this->formatSlotLabel($hourRow['hour'], $slot['minute']),
                    'bookings' => $slot['bookings'],
                    'people' => $slot['people'],
                ];
            }

            $rows[] = [
                'hour' => $hourRow['hour'],
                'label' => $this->formatHourLabel($hourRow['hour']),
                'bookings' => $hourRow['bookings'],
                'people' => $hourRow['people'],
                'slots' => $slots,
            ];
        }

        return [
            'summary' => [
                'bookings' => (int) $bookings->count(),
                'people' => (int) $bookings->sum(fn (Booking $booking) => (int) ($booking->total_count ?? 0)),
            ],
            'hours' => $rows,
        ];
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

    private function formatHourLabel(int $hour): string
    {
        $start = Carbon::createFromTime($hour, 0);
        $end = $start->copy()->addHour();

        return $start->format('H:i').'–'.$end->format('H:i');
    }

    private function formatSlotLabel(int $hour, int $minute): string
    {
        $start = Carbon::createFromTime($hour, $minute);
        $end = $start->copy()->addMinutes(5);

        return $start->format('H:i').'–'.$end->format('H:i');
    }
}
