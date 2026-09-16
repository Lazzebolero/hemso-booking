<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Tour;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TourHeadcountService
{
    public function __construct(
        private BookingParticipantService $participants,
    ) {}

    public function bookedTotal(Tour $tour): int
    {
        return (int) Booking::query()
            ->where('tour_id', $tour->id)
            ->whereNotIn('status', ['cancelled'])
            ->where('is_waitlist', false)
            ->sum('total_count');
    }

    public function syncTourToActualCount(Tour $tour, int $targetCount, int $userId, string $logMessage): void
    {
        if ($targetCount < 1) {
            throw ValidationException::withMessages([
                'actual_on_site_count' => 'Minst 1 person måste vara på plats.',
            ]);
        }

        $bookedBefore = $this->bookedTotal($tour);

        if ($targetCount === $bookedBefore) {
            return;
        }

        $diff = $targetCount - $bookedBefore;

        if ($diff > 0) {
            $this->createWalkInBooking($tour, $diff, $userId);
        } else {
            $this->decreaseBookingsBy(abs($diff), $tour, $userId);
        }

        if (class_exists(LogService::class)) {
            LogService::log(
                'tour',
                $tour->id,
                'headcount_adjusted',
                ['booked_total' => $bookedBefore],
                ['actual_total' => $targetCount],
                $logMessage
            );
        }
    }

    public function recordStartSnapshot(Tour $tour, int $bookedTotal, int $actualTotal, int $userId): void
    {
        $tour->update([
            'booked_total_at_start' => $bookedTotal,
            'actual_total_at_start' => $actualTotal,
            'headcount_adjusted_at' => $bookedTotal !== $actualTotal ? now() : null,
            'headcount_adjusted_by' => $bookedTotal !== $actualTotal ? $userId : null,
            'updated_by' => $userId,
        ]);
    }

    private function createWalkInBooking(Tour $tour, int $count, int $userId): void
    {
        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => $this->generateWalkInName(),
            'contact_name' => 'Walk-in',
            'men_count' => 0,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'unspecified_count' => $count,
            'total_count' => $count,
            'status' => 'confirmed',
            'arrival_status' => 'arrived',
            'checked_in_at' => now(),
            'is_walk_in' => true,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    private function decreaseBookingsBy(int $amount, Tour $tour, int $userId): void
    {
        $remaining = $amount;

        $remaining = $this->decreaseUnspecifiedAcrossTour($remaining, $tour, $userId);

        while ($remaining > 0) {
            $booking = Booking::query()
                ->where('tour_id', $tour->id)
                ->whereNotIn('status', ['cancelled'])
                ->where('is_waitlist', false)
                ->where('total_count', '>', 0)
                ->orderByDesc('total_count')
                ->orderBy('id')
                ->first();

            if ($booking === null) {
                throw ValidationException::withMessages([
                    'actual_on_site_count' => 'Det går inte att minska antalet ytterligare.',
                ]);
            }

            $reduceBy = min($remaining, (int) $booking->total_count);
            $newTotal = (int) $booking->total_count - $reduceBy;

            if ($newTotal === 0) {
                $booking->update([
                    'men_count' => 0,
                    'women_count' => 0,
                    'youth_count' => 0,
                    'child_count' => 0,
                    'unspecified_count' => 0,
                    'total_count' => 0,
                    'status' => 'cancelled',
                    'updated_by' => $userId,
                ]);
            } else {
                $counts = $this->reduceParticipantBreakdown(
                    (int) $booking->men_count,
                    (int) $booking->women_count,
                    (int) $booking->youth_count,
                    (int) $booking->child_count,
                    (int) $booking->unspecified_count,
                    $reduceBy
                );

                $booking->update([
                    ...$counts,
                    'updated_by' => $userId,
                ]);
            }

            $remaining -= $reduceBy;
        }
    }

    private function decreaseUnspecifiedAcrossTour(int $amount, Tour $tour, int $userId): int
    {
        $remaining = $amount;

        while ($remaining > 0) {
            $booking = Booking::query()
                ->where('tour_id', $tour->id)
                ->whereNotIn('status', ['cancelled'])
                ->where('is_waitlist', false)
                ->where('unspecified_count', '>', 0)
                ->orderByDesc('unspecified_count')
                ->orderBy('id')
                ->first();

            if ($booking === null) {
                break;
            }

            $reduceBy = min($remaining, (int) $booking->unspecified_count);
            $counts = $this->reduceParticipantBreakdown(
                (int) $booking->men_count,
                (int) $booking->women_count,
                (int) $booking->youth_count,
                (int) $booking->child_count,
                (int) $booking->unspecified_count,
                $reduceBy
            );

            $booking->update([
                ...$counts,
                'updated_by' => $userId,
            ]);

            $remaining -= $reduceBy;
        }

        return $remaining;
    }

    /**
     * @return array{
     *     men_count: int,
     *     women_count: int,
     *     youth_count: int,
     *     child_count: int,
     *     unspecified_count: int,
     *     total_count: int
     * }
     */
    private function reduceParticipantBreakdown(
        int $men,
        int $women,
        int $youth,
        int $child,
        int $unspecified,
        int $reduceBy
    ): array {
        $remaining = max(0, $reduceBy);

        $fromUnspecified = min($remaining, $unspecified);
        $unspecified -= $fromUnspecified;
        $remaining -= $fromUnspecified;

        // Säkerhet: rör aldrig ungdomar eller barn vid snabb justering — bara vuxna.
        $categories = [
            'women_count' => &$women,
            'men_count' => &$men,
        ];

        foreach ($categories as $field => &$count) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, $count);
            $count -= $take;
            $remaining -= $take;
        }

        unset($count);

        if ($remaining > 0) {
            throw ValidationException::withMessages([
                'actual_on_site_count' => 'Det går inte att minska fler vuxna. Ungdomar och barn behålls oförändrade.',
            ]);
        }

        $total = $men + $women + $youth + $child + $unspecified;

        return [
            'men_count' => $men,
            'women_count' => $women,
            'youth_count' => $youth,
            'child_count' => $child,
            'unspecified_count' => $unspecified,
            'total_count' => $total,
        ];
    }

    private function generateWalkInName(): string
    {
        do {
            $candidate = 'WALKIN-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4));
        } while (Booking::query()->where('booking_name', $candidate)->exists());

        return $candidate;
    }
}
